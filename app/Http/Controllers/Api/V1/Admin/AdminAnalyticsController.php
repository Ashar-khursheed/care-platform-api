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
     * Get dashboard overview
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
     * Get user analytics
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
     * Get revenue analytics
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
     * Get booking analytics
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
     * Get review analytics
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
     * Get provider analytics
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
     * Export bookings report
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
     * Export payments report
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
     * Export reviews report
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
     * Export users report
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
     * Export revenue summary
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
