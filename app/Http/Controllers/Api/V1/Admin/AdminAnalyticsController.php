<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
{
    protected $analyticsService;
    protected $reportService;

    public function __construct(AnalyticsService $analyticsService, ReportExportService $reportService)
    {
        $this->analyticsService = $analyticsService;
        $this->reportService = $reportService;
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/dashboard",
 *         summary="Get admin dashboard",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function dashboard()
    {
        $overview = $this->analyticsService->getDashboardOverview();

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/users",
 *         summary="Get user analytics",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function userAnalytics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->getUserAnalytics(
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/revenue",
 *         summary="Get revenue analytics",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function revenueAnalytics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->getRevenueAnalytics(
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/bookings",
 *         summary="Get booking analytics",
 *         tags={"Bookings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function bookingAnalytics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->getBookingAnalytics(
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/reviews",
 *         summary="Get review analytics",
 *         tags={"Reviews"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function reviewAnalytics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->getReviewAnalytics(
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/providers",
 *         summary="Get provider analytics",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function providerAnalytics(Request $request)
    {
        $request->validate([
            'provider_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->getProviderAnalytics(
            $request->get('provider_id'),
            $request->get('start_date'),
            $request->get('end_date')
        );

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/export/bookings",
 *         summary="Export bookings report",
 *         tags={"Bookings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function exportBookings(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,accepted,in_progress,completed,canceled',
        ]);

        return $this->reportService->exportBookingsCSV(
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('status')
        );
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/export/payments",
 *         summary="Export payments report",
 *         tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function exportPayments(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,completed,failed,refunded',
        ]);

        return $this->reportService->exportPaymentsCSV(
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('status')
        );
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/export/reviews",
 *         summary="Export reviews report",
 *         tags={"Reviews"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function exportReviews(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        return $this->reportService->exportReviewsCSV(
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('status')
        );
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/export/users",
 *         summary="Export users report",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function exportUsers(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_type' => 'nullable|in:client,provider',
        ]);

        return $this->reportService->exportUsersCSV(
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('user_type')
        );
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/admin/analytics/export/revenue-summary",
 *         summary="Export revenue summary",
 *         tags={"Analytics"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function exportRevenueSummary(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,month,year',
        ]);

        return $this->reportService->exportRevenueSummaryCSV(
            $request->get('start_date'),
            $request->get('end_date'),
            $request->get('group_by', 'day')
        );
    }
}
