<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bid;
use App\Models\ServiceListing;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\Bid\NewBidReceived;
use App\Mail\Bid\BidAccepted;
use OpenApi\Attributes as OA;

class BidController extends Controller
{
    #[OA\Post(
        path: '/api/v1/jobs/{jobId}/bids',
        summary: 'Place a bid on a job',
        security: [['bearerAuth' => []]],
        tags: ['Bids']
    )]
    #[OA\Response(response: 201, description: 'Bid placed')]
    public function store(Request $request, $jobId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string',
        ]);

        $job = ServiceListing::find($jobId);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Prevent owner from bidding on their own job
        if ($job->provider_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot bid on your own job'], 403);
        }

        // Check if already bid
        $existingBid = Bid::where('listing_id', $jobId)
            ->where('provider_id', $request->user()->id)
            ->first();

        if ($existingBid) {
            return response()->json(['message' => 'You have already placed a bid on this job'], 400);
        }

        $bid = Bid::create([
            'listing_id' => $jobId,
            'provider_id' => $request->user()->id,
            'amount' => $request->amount,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        $bid->load(['listing.provider', 'provider']);

        // Send notification to job owner (client)
        Mail::to($bid->listing->provider->email)->queue(new NewBidReceived($bid));

        return response()->json([
            'success' => true,
            'message' => 'Bid placed successfully',
            'data' => $bid
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/jobs/{jobId}/bids',
        summary: 'Get bids for a job (Client only)',
        security: [['bearerAuth' => []]],
        tags: ['Bids']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index(Request $request, $jobId)
    {
        $job = ServiceListing::find($jobId);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Only the owner (client) can see all bids
        if ($job->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bids = Bid::with('provider')
            ->where('listing_id', $jobId)
            ->orderBy('amount', 'asc') // Lowest bid first? Or created_at?
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bids
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/bids/{bidId}/accept',
        summary: 'Accept a bid',
        security: [['bearerAuth' => []]],
        tags: ['Bids']
    )]
    #[OA\Response(response: 200, description: 'Bid accepted')]
    public function accept(Request $request, $bidId)
    {
        $bid = Bid::with('listing')->find($bidId);

        if (!$bid) {
            return response()->json(['message' => 'Bid not found'], 404);
        }

        $job = $bid->listing;

        // Only the owner (client) can accept a bid
        if ($job->provider_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($bid->status !== 'pending') {
            return response()->json(['message' => 'Bid is not pending'], 400);
        }

        try {
            DB::transaction(function () use ($bid, $job) {
                // Update bid status
                $bid->update(['status' => 'accepted']);

                // Reject other bids? Optional, but good practice
                Bid::where('listing_id', $job->id)
                    ->where('id', '!=', $bid->id)
                    ->update(['status' => 'rejected']);

                // Create Booking
                Booking::create([
                    'client_id' => $job->provider_id, // The job owner is the client
                    'provider_id' => $bid->provider_id, // The bidder is the provider
                    'listing_id' => $job->id,
                    'booking_date' => now(), // Or derive from job details if available
                    'start_time' => now()->setTime(9, 0), // Default start time
                    'end_time' => now()->setTime(10, 0),   // Default end time
                    'hours' => 1,               // Default duration
                    'total_amount' => $bid->amount,
                    'hourly_rate' => $bid->amount, // Using amount as rate for now
                    'status' => 'accepted', // Auto-accept as client initiated it effectively
                    'service_location' => $job->service_location,
                ]);
                
                // Update job status - mark as unavailable as it is assigned
                $job->update(['is_available' => false]);
            });

            // Send notification to bidder
            Mail::to($bid->provider->email)->queue(new BidAccepted($bid));

            return response()->json([
                'success' => true,
                'message' => 'Bid accepted and booking created'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept bid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/my-bids',
        summary: 'Get all my bids (Provider only)',
        security: [['bearerAuth' => []]],
        tags: ['Bids']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function myBids(Request $request)
    {
        $bids = Bid::with(['listing.category', 'listing.provider'])
            ->where('provider_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'bids' => $bids->items(),
                'pagination' => [
                    'total' => $bids->total(),
                    'per_page' => $bids->perPage(),
                    'current_page' => $bids->currentPage(),
                    'last_page' => $bids->lastPage(),
                ]
            ]
        ], 200);
    }
}
