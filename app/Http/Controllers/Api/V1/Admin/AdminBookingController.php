<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    /**
     * Get all bookings with filters
     */
    public function index(Request $request)
    {
        $query = Booking::with(['client', 'provider', 'listing.category']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
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
     * Get booking statistics
     */
    public function statistics()
    {
        $stats = [
            'total_bookings' => Booking::count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'accepted' => Booking::where('status', 'accepted')->count(),
            'in_progress' => Booking::where('status', 'in_progress')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'total_revenue' => Booking::where('status', 'completed')->sum('total_amount'),
            'pending_payments' => Booking::where('payment_status', 'pending')->count(),
            'paid_bookings' => Booking::where('payment_status', 'paid')->count(),
        ];

        // Recent bookings (last 7 days)
        $recentBookings = Booking::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Top providers
        $topProviders = Booking::where('status', 'completed')
            ->selectRaw('provider_id, COUNT(*) as bookings_count, SUM(total_amount) as total_earned')
            ->groupBy('provider_id')
            ->orderBy('bookings_count', 'desc')
            ->limit(10)
            ->with('provider:id,first_name,last_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_bookings' => $recentBookings,
                'top_providers' => $topProviders,
            ]
        ], 200);
    }

    /**
     * Get specific booking
     */
    public function show($id)
    {
        $booking = Booking::with(['client', 'provider', 'listing.category'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking)
        ], 200);
    }

    /**
     * Cancel booking as admin
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

            // TODO: Send notifications to both client and provider

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully by admin'
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
     * Delete booking permanently
     */
    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        try {
            $booking->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted permanently'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
