<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

        /**
         * @OA\Get(
         *     path="/v1/analytics/provider/dashboard",
         *     operationId="analyticsProviderdashboard",
         *     tags={"Analytics"},
         *     summary="Get provider dashboard analytics",
         *     security={{"bearerAuth":{}}},
         *     @OA\Response(response=200, description="Success"),
         *     @OA\Response(response=401, description="Unauthorized"),
         *     @OA\Response(response=404, description="Not found"),
         *     @OA\Response(response=500, description="Server error")
         * )
         */
    public function providerDashboard(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for providers.',
            ], 403);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $analytics = $this->analyticsService->getProviderAnalytics(
            $user->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get provider earnings report
     */
    public function providerEarnings(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for providers.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $groupBy = $request->get('group_by', 'day');

        $dateFormat = match($groupBy) {
            'month' => '%Y-%m',
            'week' => '%Y-%u',
            default => '%Y-%m-%d',
        };

        $earnings = \App\Models\Payment::where('status', 'completed')
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('provider_id', $user->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, 
                         COUNT(*) as transaction_count,
                         SUM(provider_amount) as total_earnings")
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $totalEarnings = $earnings->sum('total_earnings');

        return response()->json([
            'success' => true,
            'data' => [
                'total_earnings' => round($totalEarnings, 2),
                'earnings_breakdown' => $earnings,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'group_by' => $groupBy,
                ],
            ],
        ]);
    }

    /**
     * Get provider booking statistics
     */
    public function providerBookingStats(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for providers.',
            ], 403);
        }

        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $bookings = \App\Models\Booking::where('provider_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $total = $bookings->sum('count');
        $completed = $bookings->firstWhere('status', 'completed')->count ?? 0;
        $canceled = $bookings->firstWhere('status', 'canceled')->count ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_bookings' => $total,
                'by_status' => $bookings,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) . '%' : '0%',
                'cancellation_rate' => $total > 0 ? round(($canceled / $total) * 100, 2) . '%' : '0%',
            ],
        ]);
    }

    /**
     * Get provider review statistics
     */
    public function providerReviewStats(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for providers.',
            ], 403);
        }

        $reviews = \App\Models\Review::where('provider_id', $user->id)
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();

        $totalReviews = $reviews->sum('count');
        $avgRating = \App\Models\Review::where('provider_id', $user->id)
            ->approved()
            ->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'average_rating' => round($avgRating, 2),
                'rating_distribution' => $reviews,
            ],
        ]);
    }

    /**
     * Get client booking history analytics
     */
    public function clientBookingHistory(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for clients.',
            ], 403);
        }

        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $bookings = \App\Models\Booking::where('client_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total_spent')
            ->groupBy('status')
            ->get();

        $totalBookings = $bookings->sum('count');
        $totalSpent = $bookings->sum('total_spent');

        return response()->json([
            'success' => true,
            'data' => [
                'total_bookings' => $totalBookings,
                'total_spent' => round($totalSpent, 2),
                'by_status' => $bookings,
                'average_booking_value' => $totalBookings > 0 ? round($totalSpent / $totalBookings, 2) : 0,
            ],
        ]);
    }

    /**
     * Get client spending analytics
     */
    public function clientSpending(Request $request)
    {
        $user = $request->user();

        if ($user->user_type !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'This endpoint is only for clients.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());
        $groupBy = $request->get('group_by', 'day');

        $dateFormat = match($groupBy) {
            'month' => '%Y-%m',
            'week' => '%Y-%u',
            default => '%Y-%m-%d',
        };

        $spending = \App\Models\Payment::where('status', 'completed')
            ->whereHas('booking', function ($query) use ($user) {
                $query->where('client_id', $user->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, 
                         COUNT(*) as transaction_count,
                         SUM(amount) as total_spent")
            ->groupBy('period')
            ->orderBy('period', 'asc')
            ->get();

        $totalSpent = $spending->sum('total_spent');

        return response()->json([
            'success' => true,
            'data' => [
                'total_spent' => round($totalSpent, 2),
                'spending_breakdown' => $spending,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'group_by' => $groupBy,
                ],
            ],
        ]);
    }
}
