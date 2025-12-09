<?php

namespace App\Services;

use App\Models\User;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\ServiceListing;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get user analytics
     */
    public function getUserAnalytics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        // Total users
        $totalUsers = User::count();
        $totalClients = User::where('user_type', 'client')->count();
        $totalProviders = User::where('user_type', 'provider')->count();

        // New users in date range
        $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $newClients = User::where('user_type', 'client')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        $newProviders = User::where('user_type', 'provider')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Active users (users with activity in date range)
        $activeUsers = User::whereHas('bookings', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        })->count();

        // Verified users
        $verifiedUsers = User::where('is_verified', true)->count();

        // User registration trend (daily)
        $registrationTrend = User::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // User by type trend
        $usersByType = User::selectRaw('user_type, COUNT(*) as count')
            ->groupBy('user_type')
            ->get();

        return [
            'overview' => [
                'total_users' => $totalUsers,
                'total_clients' => $totalClients,
                'total_providers' => $totalProviders,
                'new_users' => $newUsers,
                'new_clients' => $newClients,
                'new_providers' => $newProviders,
                'active_users' => $activeUsers,
                'verified_users' => $verifiedUsers,
            ],
            'registration_trend' => $registrationTrend,
            'users_by_type' => $usersByType,
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        // Total revenue
        $totalRevenue = Payment::where('status', 'completed')
            ->sum('amount');

        // Revenue in date range
        $periodRevenue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Platform fees collected
        $platformFees = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('platform_fee');

        // Revenue by payment method
        $revenueByMethod = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        // Revenue by category
        $revenueByCategory = Payment::where('payments.status', 'completed')
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('service_listings', 'bookings.listing_id', '=', 'service_listings.id')
            ->join('service_categories', 'service_listings.category_id', '=', 'service_categories.id')
            ->selectRaw('service_categories.name as category, SUM(payments.amount) as total')
            ->groupBy('service_categories.id', 'service_categories.name')
            ->orderBy('total', 'desc')
            ->get();

        // Daily revenue trend
        $revenueTrend = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Average transaction value
        $avgTransactionValue = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('amount');

        // Subscription revenue
        $subscriptionRevenue = UserSubscription::whereIn('status', ['trial', 'active'])
            ->sum('amount');

        return [
            'overview' => [
                'total_revenue' => round($totalRevenue, 2),
                'period_revenue' => round($periodRevenue, 2),
                'platform_fees' => round($platformFees, 2),
                'avg_transaction_value' => round($avgTransactionValue, 2),
                'subscription_revenue' => round($subscriptionRevenue, 2),
            ],
            'revenue_by_method' => $revenueByMethod,
            'revenue_by_category' => $revenueByCategory,
            'revenue_trend' => $revenueTrend,
        ];
    }

    /**
     * Get booking analytics
     */
    public function getBookingAnalytics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        // Total bookings
        $totalBookings = Booking::count();

        // Bookings in date range
        $periodBookings = Booking::whereBetween('created_at', [$startDate, $endDate])->count();

        // Bookings by status
        $bookingsByStatus = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Completion rate
        $completedBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        $completionRate = $periodBookings > 0 ? round(($completedBookings / $periodBookings) * 100, 2) : 0;

        // Cancellation rate
        $canceledBookings = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'canceled')
            ->count();
        $cancellationRate = $periodBookings > 0 ? round(($canceledBookings / $periodBookings) * 100, 2) : 0;

        // Average booking value
        $avgBookingValue = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->avg('total_amount');

        // Booking trend
        $bookingTrend = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Peak booking hours
        $peakHours = Booking::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Bookings by category
        $bookingsByCategory = Booking::whereBetween('bookings.created_at', [$startDate, $endDate])
            ->join('service_listings', 'bookings.listing_id', '=', 'service_listings.id')
            ->join('service_categories', 'service_listings.category_id', '=', 'service_categories.id')
            ->selectRaw('service_categories.name as category, COUNT(*) as count')
            ->groupBy('service_categories.id', 'service_categories.name')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'overview' => [
                'total_bookings' => $totalBookings,
                'period_bookings' => $periodBookings,
                'completed_bookings' => $completedBookings,
                'canceled_bookings' => $canceledBookings,
                'completion_rate' => $completionRate . '%',
                'cancellation_rate' => $cancellationRate . '%',
                'avg_booking_value' => round($avgBookingValue, 2),
            ],
            'bookings_by_status' => $bookingsByStatus,
            'booking_trend' => $bookingTrend,
            'peak_hours' => $peakHours,
            'bookings_by_category' => $bookingsByCategory,
        ];
    }

    /**
     * Get review analytics
     */
    public function getReviewAnalytics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        // Total reviews
        $totalReviews = Review::approved()->count();

        // Reviews in date range
        $periodReviews = Review::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Average rating
        $avgRating = Review::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('rating');

        // Rating distribution
        $ratingDistribution = Review::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();

        // Reviews with response
        $reviewsWithResponse = Review::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('provider_response')
            ->count();
        $responseRate = $periodReviews > 0 ? round(($reviewsWithResponse / $periodReviews) * 100, 2) : 0;

        // Review trend
        $reviewTrend = Review::approved()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, AVG(rating) as avg_rating')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Top rated providers
        $topRatedProviders = User::where('user_type', 'provider')
            ->where('average_rating', '>', 0)
            ->orderBy('average_rating', 'desc')
            ->orderBy('reviews_count', 'desc')
            ->limit(10)
            ->select('id', 'first_name', 'last_name', 'average_rating', 'reviews_count')
            ->get();

        return [
            'overview' => [
                'total_reviews' => $totalReviews,
                'period_reviews' => $periodReviews,
                'avg_rating' => round($avgRating, 2),
                'reviews_with_response' => $reviewsWithResponse,
                'response_rate' => $responseRate . '%',
            ],
            'rating_distribution' => $ratingDistribution,
            'review_trend' => $reviewTrend,
            'top_rated_providers' => $topRatedProviders,
        ];
    }

    /**
     * Get provider analytics
     */
    public function getProviderAnalytics($providerId = null, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : now();

        $query = Booking::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        // Total bookings
        $totalBookings = $query->count();

        // Total earnings
        $totalEarnings = Payment::whereIn('booking_id', $query->pluck('id'))
            ->where('status', 'completed')
            ->sum('provider_amount');

        // Average rating
        $avgRating = Review::approved()
            ->when($providerId, function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('rating');

        // Response rate
        $totalReviews = Review::approved()
            ->when($providerId, function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $responsedReviews = Review::approved()
            ->when($providerId, function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('provider_response')
            ->count();

        $responseRate = $totalReviews > 0 ? round(($responsedReviews / $totalReviews) * 100, 2) : 0;

        // Top providers (if not specific provider)
        if (!$providerId) {
            $topProviders = User::where('user_type', 'provider')
                ->withCount(['bookingsAsProvider' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }])
                ->orderBy('bookings_as_provider_count', 'desc')
                ->limit(10)
                ->get();
        }

        return [
            'overview' => [
                'total_bookings' => $totalBookings,
                'total_earnings' => round($totalEarnings, 2),
                'avg_rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
                'response_rate' => $responseRate . '%',
            ],
            'top_providers' => $topProviders ?? null,
        ];
    }

    /**
     * Get dashboard overview
     */
    public function getDashboardOverview()
    {
        $today = now();
        $yesterday = now()->subDay();
        $last30Days = now()->subDays(30);

        return [
            'users' => [
                'total' => User::count(),
                'new_today' => User::whereDate('created_at', $today)->count(),
                'new_yesterday' => User::whereDate('created_at', $yesterday)->count(),
                'active_providers' => User::where('user_type', 'provider')->where('is_verified', true)->count(),
            ],
            'bookings' => [
                'total' => Booking::count(),
                'today' => Booking::whereDate('created_at', $today)->count(),
                'pending' => Booking::where('status', 'pending')->count(),
                'completed_last_30_days' => Booking::where('status', 'completed')
                    ->where('created_at', '>=', $last30Days)
                    ->count(),
            ],
            'revenue' => [
                'total' => round(Payment::where('status', 'completed')->sum('amount'), 2),
                'today' => round(Payment::where('status', 'completed')
                    ->whereDate('created_at', $today)
                    ->sum('amount'), 2),
                'last_30_days' => round(Payment::where('status', 'completed')
                    ->where('created_at', '>=', $last30Days)
                    ->sum('amount'), 2),
                'pending' => round(Payment::where('status', 'pending')->sum('amount'), 2),
            ],
            'reviews' => [
                'total' => Review::approved()->count(),
                'avg_rating' => round(Review::approved()->avg('rating'), 2),
                'pending' => Review::pending()->count(),
            ],
            'listings' => [
                'total' => ServiceListing::count(),
                'active' => ServiceListing::where('is_available', true)->count(),
                'pending' => ServiceListing::where('status', 'pending')->count(),
            ],
            'subscriptions' => [
                'active' => UserSubscription::active()->count(),
                'trial' => UserSubscription::trial()->count(),
                'mrr' => round(
                    UserSubscription::whereIn('status', ['trial', 'active'])
                        ->where('billing_cycle', 'monthly')
                        ->sum('amount') +
                    (UserSubscription::whereIn('status', ['trial', 'active'])
                        ->where('billing_cycle', 'yearly')
                        ->sum('amount') / 12),
                    2
                ),
            ],
        ];
    }
}