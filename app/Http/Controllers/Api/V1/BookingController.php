<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookingStoreRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\ServiceListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenApi\Attributes as OA;
use App\Services\StripeService;
use App\Models\Payment;

class BookingController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }
    #[OA\Get(
        path: '/api/v1/bookings',
        summary: 'Get user bookings',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['client', 'provider', 'listing.category']);

        // Filter by role - allow user to see bookings where they are client OR provider
        $query->where(function($q) use ($user) {
            $q->where('client_id', $user->id)
              ->orWhere('provider_id', $user->id);
        });

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type (upcoming/past)
        if ($request->get('type') === 'upcoming') {
            $query->upcoming();
        } elseif ($request->get('type') === 'past') {
            $query->past();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'booking_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $bookings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => BookingResource::collection($bookings),
                'pagination' => [
                    'total' => $bookings->total(),
                    'per_page' => $bookings->perPage(),
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                ]
            ]
        ], 200);
    }

    #[OA\Get(
        path: '/api/v1/bookings/{id}',
        summary: 'Get booking details',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with(['client', 'provider', 'listing.category'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check authorization
        if ($booking->client_id !== $user->id && $booking->provider_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this booking'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking)
        ], 200);
    }

    #[OA\Post(
        path: '/api/v1/bookings',
        summary: 'Create new booking (or apply to job)',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['listing_id', 'start_date', 'end_date', 'start_time', 'end_time', 'service_location'],
            properties: [
                new OA\Property(property: 'listing_id', type: 'integer', example: 1),
                new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2024-01-01'),
                new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2024-01-01'),
                new OA\Property(property: 'start_time', type: 'string', example: '09:00:00'),
                new OA\Property(property: 'end_time', type: 'string', example: '17:00:00'),
                new OA\Property(property: 'service_location', type: 'string', example: '123 Main St'),
                new OA\Property(property: 'special_requirements', type: 'string', example: 'Bring own tools')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Booking created')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Listing not found')]
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // -------------------
            // Validate input
            // -------------------
            $validated = $request->validate([
                'listing_id' => 'required|exists:service_listings,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                'service_location' => 'required|string|max:500',
                'special_requirements' => 'nullable|string|max:1000',
            ]);

            // -------------------
            // Find listing
            // -------------------
            $listing = ServiceListing::with('provider')->find($validated['listing_id']);

            if (!$listing || !$listing->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service listing is not available'
                ], 400);
            }

            // -------------------
            // Parse start & end datetime
            // -------------------
            $start = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time']);
            $end = Carbon::parse($validated['end_date'] . ' ' . $validated['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The end time must be after the start time.'
                ], 400);
            }

            // Calculate total hours
            $hours = abs($end->diffInMinutes($start) / 60);


            if ($hours > 12 * ($end->diffInDays($start) + 1)) { // max 12 hours per day
                return response()->json([
                    'success' => false,
                    'message' => 'Booking cannot exceed 12 hours per day.'
                ], 400);
            }

            $totalAmount = abs($hours * $listing->hourly_rate);

            // -------------------
            // Determine Roles (Bidirectional)
            // -------------------
            // If Listing Creator is a Client -> This is a JOB POST. Auth User (Provider) is applying.
            // If Listing Creator is a Provider -> This is a SERVICE. Auth User (Client) is booking.

            $listingCreator = $listing->provider; // Relation is named 'provider' but means 'creator'

            if ($listingCreator->user_type === 'client') {
                // Case 1: Job Application (Provider applying to Client's Job)
                $clientId = $listingCreator->id; // The Job Poster
                $providerId = $user->id;         // The Applicant

                if ($user->user_type !== 'provider') {
                    return response()->json(['success' => false, 'message' => 'Only providers can apply to jobs.'], 403);
                }
            } else {
                // Case 2: Service Booking (Client booking Provider's Service)
                $clientId = $user->id;           // The Booker
                $providerId = $listingCreator->id; // The Service Provider

                // Allow providers to book other providers? Maybe not.
                if ($user->id === $providerId) {
                    return response()->json(['success' => false, 'message' => 'You cannot book your own listing.'], 400);
                }
            }

            // -------------------
            // Create booking
            // -------------------
            $booking = Booking::create([
                'client_id' => $clientId,
                'provider_id' => $providerId,
                'booking_date' => $validated['start_date'], // must include
                'listing_id' => $listing->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'hours' => $hours,
                'hourly_rate' => $listing->hourly_rate,
                'total_amount' => $totalAmount,
                'service_location' => $validated['service_location'],
                'special_requirements' => $validated['special_requirements'] ?? null,
                'status' => 'pending',
            ]);

            $booking->load(['client', 'provider', 'listing.category']);

            return response()->json([
                'success' => true,
                'message' => 'Booking request sent successfully',
                'data' => new BookingResource($booking)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/provider/bookings/{id}/accept',
        summary: 'Accept booking',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function accept(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check authorization
        if ($booking->provider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$booking->canBeAccepted()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be accepted in current status'
            ], 400);
        }

        try {
            $booking->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            $booking->load(['client', 'provider', 'listing.category']);

            // TODO: Send notification to client

            return response()->json([
                'success' => true,
                'message' => 'Booking accepted successfully',
                'data' => new BookingResource($booking)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/provider/bookings/{id}/reject',
        summary: 'Reject booking',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Scheduling conflict')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check authorization
        if ($booking->provider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$booking->canBeAccepted()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be rejected in current status'
            ], 400);
        }

        try {
            $booking->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $request->reason,
            ]);

            // Process refund if payment exists
            $payment = Payment::where('booking_id', $booking->id)->where('status', 'succeeded')->first();
            if ($payment) {
                $this->stripeService->processRefund($payment, null, 'Booking rejected by provider');
                $payment->update(['status' => 'refunded', 'refunded_at' => now(), 'refund_amount' => $payment->amount]);
            }

            // TODO: Send notification to client

            return response()->json([
                'success' => true,
                'message' => 'Booking rejected',
                'data' => [
                    'rejection_reason' => $request->reason
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/bookings/{id}/cancel',
        summary: 'Cancel booking',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['reason'],
            properties: [
                new OA\Property(property: 'reason', type: 'string', example: 'Plans changed')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Check authorization
        if ($booking->client_id !== $request->user()->id && $booking->provider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be cancelled in current status'
            ], 400);
        }

        try {
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => now(),
            ]);

            // Process refund if payment exists and it's appropriate (e.g. within policy)
            // For now, simple logic: if paid, refund.
            $payment = Payment::where('booking_id', $booking->id)->where('status', 'succeeded')->first();
            if ($payment) {
                 // Determine refund amount? Full refund for now.
                $this->stripeService->processRefund($payment, null, 'Booking cancelled');
                $payment->update(['status' => 'refunded', 'refunded_at' => now(), 'refund_amount' => $payment->amount]);
            }

            // TODO: Send notification to other party

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/bookings/{id}/in-progress',
        summary: 'Mark booking as in progress',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    public function markInProgress(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->provider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => 'Only accepted bookings can be marked as in progress'
            ], 400);
        }

        try {
            $booking->update(['status' => 'in_progress']);

            return response()->json([
                'success' => true,
                'message' => 'Booking marked as in progress'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/bookings/{id}/complete',
        summary: 'Mark booking as completed',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    public function markCompleted(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->provider_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($booking->status, ['accepted', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot be marked as completed in current status'
            ], 400);
        }

        try {
            $booking->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // TODO: Send notification to client for review

            return response()->json([
                'success' => true,
                'message' => 'Booking marked as completed'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/v1/bookings/{id}',
        summary: 'Update booking details',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'service_location', type: 'string'),
                new OA\Property(property: 'special_requirements', type: 'string')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->client_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $booking->update($request->only(['service_location', 'special_requirements']));
        return response()->json(['success' => true, 'data' => new BookingResource($booking)]);
    }

    #[OA\Put(
        path: '/api/v1/bookings/{id}/start',
        summary: 'Start the booking',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    public function start(Request $request, $id)
    {
        return $this->markInProgress($request, $id);
    }

    #[OA\Delete(
        path: '/api/v1/bookings/{id}',
        summary: 'Delete a booking',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
    public function destroy(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        if ($booking->client_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $booking->delete();
        return response()->json(['success' => true, 'message' => 'Booking deleted']);
    }

    #[OA\Get(
        path: '/api/v1/bookings/provider/pending',
        summary: 'Get pending bookings for provider',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function providerPending(Request $request)
    {
        $request->merge(['status' => 'pending']);
        return $this->index($request);
    }

    #[OA\Get(
        path: '/api/v1/bookings/provider/upcoming',
        summary: 'Get upcoming bookings for provider',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function providerUpcoming(Request $request)
    {
        $request->merge(['type' => 'upcoming']);
        return $this->index($request);
    }

    #[OA\Get(
        path: '/api/v1/bookings/client/upcoming',
        summary: 'Get upcoming bookings for client',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function clientUpcoming(Request $request)
    {
        $request->merge(['type' => 'upcoming']);
        return $this->index($request);
    }

    #[OA\Get(
        path: '/api/v1/bookings/statistics',
        summary: 'Get booking statistics',
        security: [['bearerAuth' => []]],
        tags: ['Bookings']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function statistics(Request $request)
    {
        $user = $request->user();

        if ($user->isClient()) {
            $stats = [
                'total_bookings' => Booking::where('client_id', $user->id)->count(),
                'pending' => Booking::where('client_id', $user->id)->where('status', 'pending')->count(),
                'accepted' => Booking::where('client_id', $user->id)->where('status', 'accepted')->count(),
                'in_progress' => Booking::where('client_id', $user->id)->where('status', 'in_progress')->count(),
                'completed' => Booking::where('client_id', $user->id)->where('status', 'completed')->count(),
                'cancelled' => Booking::where('client_id', $user->id)->where('status', 'cancelled')->count(),
                'total_spent' => Booking::where('client_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ];
        } elseif ($user->isProvider()) {
            $stats = [
                'total_bookings' => Booking::where('provider_id', $user->id)->count(),
                'pending' => Booking::where('provider_id', $user->id)->where('status', 'pending')->count(),
                'accepted' => Booking::where('provider_id', $user->id)->where('status', 'accepted')->count(),
                'in_progress' => Booking::where('provider_id', $user->id)->where('status', 'in_progress')->count(),
                'completed' => Booking::where('provider_id', $user->id)->where('status', 'completed')->count(),
                'cancelled' => Booking::where('provider_id', $user->id)->where('status', 'cancelled')->count(),
                'total_earned' => Booking::where('provider_id', $user->id)
                    ->where('status', 'completed')
                    ->sum('total_amount'),
            ];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ], 200);
    }
}
