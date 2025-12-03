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

class BookingController extends Controller
{
    /**
     * Get bookings for authenticated user (client or provider)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['client', 'provider', 'listing.category']);

        // Filter by role
        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isProvider()) {
            $query->where('provider_id', $user->id);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

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

    /**
     * Get specific booking
     */
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

    /**
     * Create new booking (Client only)
     */
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

        // -------------------
        // Calculate hours
        // -------------------
        $hours = $end->diffInMinutes($start) / 60;
        if ($hours < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Booking must be at least 1 hour.'
            ], 400);
        }
        if ($hours > 12 * ($end->diffInDays($start) + 1)) { // max 12 hours per day
            return response()->json([
                'success' => false,
                'message' => 'Booking cannot exceed 12 hours per day.'
            ], 400);
        }

        $totalAmount = $hours * $listing->hourly_rate;

        // -------------------
        // Create booking
        // -------------------
        $booking = Booking::create([
            'client_id' => $user->id,
            'provider_id' => $listing->provider_id,
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


    /**
     * Accept booking (Provider only)
     */
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

    /**
     * Reject booking (Provider only)
     */
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

    /**
     * Cancel booking (Client or Provider)
     */
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

    /**
     * Mark booking as in progress (Provider only)
     */
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

    /**
     * Mark booking as completed (Provider only)
     */
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

    /**
     * Get booking statistics
     */
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