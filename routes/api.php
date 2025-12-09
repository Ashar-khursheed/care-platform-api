<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\AnalyticsController;

// Admin Controllers
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminMessageController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;

// Mobile Controller
use App\Http\Controllers\Api\V1\Mobile\MobileApiController;

// Middleware
use App\Http\Middleware\ResponseCompressionMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes - Care Platform v1.0.0
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1
| 11 Modules Complete: Auth, Profile, Listings, Bookings, Reviews, 
| Payments, Messaging, Notifications, Subscriptions, Analytics, Mobile
|
*/

Route::prefix('v1')->group(function () {

    // ==============================================
    // PUBLIC ROUTES (No Authentication Required)
    // ==============================================

    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ==============================================
    // MODULE 1: AUTHENTICATION
    // ==============================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    });

    // ==============================================
    // PUBLIC LISTING & CATEGORY ROUTES
    // ==============================================
    
    // Categories (Public)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    // Listings (Public - Browse without auth)
    Route::get('/listings', [ListingController::class, 'index']);
    Route::get('/listings/{id}', [ListingController::class, 'show']);
    Route::get('/listings/{id}/reviews', [ReviewController::class, 'listingReviews']);

    // ==============================================
    // MODULE 9: SUBSCRIPTION PLANS (Public)
    // ==============================================
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);

    // ==============================================
    // PROTECTED ROUTES (Authentication Required)
    // ==============================================
    Route::middleware('auth:sanctum')->group(function () {

        // ==============================================
        // MODULE 1: AUTHENTICATION (Protected)
        // ==============================================
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });

        // ==============================================
        // MODULE 2: PROFILE MANAGEMENT
        // ==============================================
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/photo', [ProfileController::class, 'uploadPhoto']);
            Route::delete('/photo', [ProfileController::class, 'deletePhoto']);
            Route::put('/settings', [ProfileController::class, 'updateSettings']);
            
            // Profile Documents
            Route::get('/documents', [ProfileController::class, 'documents']);
            Route::post('/documents', [ProfileController::class, 'uploadDocument']);
            Route::delete('/documents/{id}', [ProfileController::class, 'deleteDocument']);
        });

        // ==============================================
        // MODULE 3: SERVICE LISTINGS
        // ==============================================
        
        // User's own listings
        Route::get('/my-listings', [ListingController::class, 'myListings']);
        Route::post('/listings', [ListingController::class, 'store']);
        Route::put('/listings/{id}', [ListingController::class, 'update']);
        Route::delete('/listings/{id}', [ListingController::class, 'destroy']);
        
        // Listing Images
        Route::post('/listings/{id}/images', [ListingController::class, 'uploadImages']);
        Route::delete('/listings/{listingId}/images/{imageId}', [ListingController::class, 'deleteImage']);
        
        // Listing Actions
        Route::put('/listings/{id}/toggle-status', [ListingController::class, 'toggleStatus']);
        Route::put('/listings/{id}/toggle-featured', [ListingController::class, 'toggleFeatured']);

        // ==============================================
        // MODULE 4: BOOKING SYSTEM
        // ==============================================
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::post('/', [BookingController::class, 'store']);
            Route::get('/{id}', [BookingController::class, 'show']);
            Route::put('/{id}', [BookingController::class, 'update']);
            Route::delete('/{id}', [BookingController::class, 'destroy']);
            
            // Booking Actions
            Route::put('/{id}/accept', [BookingController::class, 'accept']);
            Route::put('/{id}/reject', [BookingController::class, 'reject']);
            Route::put('/{id}/start', [BookingController::class, 'start']);
            Route::put('/{id}/complete', [BookingController::class, 'complete']);
            Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
            
            // Provider/Client specific
            Route::get('/provider/pending', [BookingController::class, 'providerPending']);
            Route::get('/provider/upcoming', [BookingController::class, 'providerUpcoming']);
            Route::get('/client/upcoming', [BookingController::class, 'clientUpcoming']);
        });

        // ==============================================
        // MODULE 5: REVIEWS & RATINGS
        // ==============================================
        Route::prefix('reviews')->group(function () {
            Route::get('/', [ReviewController::class, 'index']);
            Route::post('/', [ReviewController::class, 'store']);
            Route::get('/{id}', [ReviewController::class, 'show']);
            Route::put('/{id}', [ReviewController::class, 'update']);
            Route::delete('/{id}', [ReviewController::class, 'destroy']);
            
            // Review Actions
            Route::post('/{id}/response', [ReviewController::class, 'addResponse']);
            Route::put('/{id}/response', [ReviewController::class, 'updateResponse']);
            Route::delete('/{id}/response', [ReviewController::class, 'deleteResponse']);
            Route::post('/{id}/helpful', [ReviewController::class, 'markHelpful']);
            
            // My Reviews
            Route::get('/my/given', [ReviewController::class, 'myGivenReviews']);
            Route::get('/my/received', [ReviewController::class, 'myReceivedReviews']);
        });

        // ==============================================
        // MODULE 6: PAYMENT INTEGRATION
        // ==============================================
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'index']);
            Route::post('/create-intent', [PaymentController::class, 'createPaymentIntent']);
            Route::post('/', [PaymentController::class, 'store']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::post('/{id}/refund', [PaymentController::class, 'refund']);
            
            // Payouts
            Route::get('/payouts/balance', [PaymentController::class, 'getBalance']);
            Route::get('/payouts/history', [PaymentController::class, 'payoutHistory']);
            Route::post('/payouts/request', [PaymentController::class, 'requestPayout']);
            
            // Transactions
            Route::get('/transactions/history', [PaymentController::class, 'transactionHistory']);
        });

        // ==============================================
        // MODULE 7: MESSAGING SYSTEM
        // ==============================================
        Route::prefix('conversations')->group(function () {
            Route::get('/', [MessageController::class, 'conversations']);
            Route::post('/', [MessageController::class, 'createConversation']);
            Route::get('/{id}', [MessageController::class, 'show']);
            Route::delete('/{id}', [MessageController::class, 'deleteConversation']);
            
            // Messages
            Route::get('/{id}/messages', [MessageController::class, 'messages']);
            Route::post('/{id}/messages', [MessageController::class, 'sendMessage']);
            
            // Message Actions
            Route::put('/messages/{id}/read', [MessageController::class, 'markAsRead']);
            Route::post('/messages/{id}/flag', [MessageController::class, 'flagMessage']);
            Route::delete('/messages/{id}', [MessageController::class, 'deleteMessage']);
            
            // Unread Count
            Route::get('/unread/count', [MessageController::class, 'unreadCount']);
        });

        // ==============================================
        // MODULE 8: NOTIFICATIONS SYSTEM
        // ==============================================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/{id}', [NotificationController::class, 'show']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
            
            // Notification Preferences
            Route::get('/preferences/get', [NotificationController::class, 'getPreferences']);
            Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
            
            // Unread Count
            Route::get('/unread/count', [NotificationController::class, 'unreadCount']);
        });

        // ==============================================
        // MODULE 9: SUBSCRIPTION PLANS
        // ==============================================
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [SubscriptionController::class, 'current']);
            Route::get('/history', [SubscriptionController::class, 'history']);
            Route::get('/usage', [SubscriptionController::class, 'usage']);
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
            Route::post('/upgrade', [SubscriptionController::class, 'upgrade']);
            Route::post('/downgrade', [SubscriptionController::class, 'downgrade']);
            Route::post('/cancel', [SubscriptionController::class, 'cancel']);
            Route::post('/resume', [SubscriptionController::class, 'resume']);
        });

        // ==============================================
        // MODULE 10: ANALYTICS & REPORTS (User)
        // ==============================================
        Route::prefix('analytics')->group(function () {
            
            // Provider Analytics
            Route::get('/provider/dashboard', [AnalyticsController::class, 'providerDashboard']);
            Route::get('/provider/earnings', [AnalyticsController::class, 'providerEarnings']);
            Route::get('/provider/bookings', [AnalyticsController::class, 'providerBookingStats']);
            Route::get('/provider/reviews', [AnalyticsController::class, 'providerReviewStats']);
            
            // Client Analytics
            Route::get('/client/bookings', [AnalyticsController::class, 'clientBookingHistory']);
            Route::get('/client/spending', [AnalyticsController::class, 'clientSpending']);
        });

        // ==============================================
        // MODULE 11: MOBILE API OPTIMIZATION
        // ==============================================
        Route::prefix('mobile')->middleware(ResponseCompressionMiddleware::class)->group(function () {
            
            // Home & Discovery
            Route::get('/home', [MobileApiController::class, 'getHomeData']);
            Route::get('/listings', [MobileApiController::class, 'getListings']);
            Route::get('/listings/{id}', [MobileApiController::class, 'getListingDetails']);
            Route::get('/listings/{id}/reviews', [MobileApiController::class, 'getListingReviews']);
            Route::get('/categories', [MobileApiController::class, 'getCategories']);
            
            // Search
            Route::get('/search/autocomplete', [MobileApiController::class, 'searchAutocomplete']);
            
            // User Data
            Route::get('/bookings', [MobileApiController::class, 'getUserBookings']);
            Route::get('/notifications', [MobileApiController::class, 'getNotifications']);
            
            // App Config
            Route::get('/config', [MobileApiController::class, 'getAppConfig']);
        });

    });

    // ==============================================
    // ADMIN ROUTES (Admin Authentication Required)
    // ==============================================
    Route::middleware(['auth:sanctum', 'check.admin'])->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminUserController::class, 'dashboard']);

        // ==============================================
        // MODULE 2.5: ADMIN USER MANAGEMENT
        // ==============================================
        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::get('/{id}', [AdminUserController::class, 'show']);
            Route::put('/{id}', [AdminUserController::class, 'update']);
            Route::delete('/{id}', [AdminUserController::class, 'destroy']);
            Route::put('/{id}/verify', [AdminUserController::class, 'verify']);
            Route::put('/{id}/ban', [AdminUserController::class, 'ban']);
            Route::put('/{id}/unban', [AdminUserController::class, 'unban']);
            
            // User Statistics
            Route::get('/statistics/overview', [AdminUserController::class, 'statistics']);
        });

        // ==============================================
        // MODULE 2.5: ADMIN DOCUMENT VERIFICATION
        // ==============================================
        Route::prefix('documents')->group(function () {
            Route::get('/', [AdminDocumentController::class, 'index']);
            Route::get('/{id}', [AdminDocumentController::class, 'show']);
            Route::put('/{id}/verify', [AdminDocumentController::class, 'verify']);
            Route::put('/{id}/reject', [AdminDocumentController::class, 'reject']);
            Route::delete('/{id}', [AdminDocumentController::class, 'destroy']);
        });

        // ==============================================
        // MODULE 3: ADMIN LISTING MANAGEMENT
        // ==============================================
        Route::prefix('listings')->group(function () {
            Route::get('/pending', [AdminListingController::class, 'pending']);
            Route::get('/', [AdminListingController::class, 'index']);
            Route::get('/{id}', [AdminListingController::class, 'show']);
            Route::put('/{id}', [AdminListingController::class, 'update']);
            Route::delete('/{id}', [AdminListingController::class, 'destroy']);
            Route::put('/{id}/approve', [AdminListingController::class, 'approve']);
            Route::put('/{id}/reject', [AdminListingController::class, 'reject']);
            Route::put('/{id}/feature', [AdminListingController::class, 'toggleFeatured']);
        });

        // ==============================================
        // MODULE 4: ADMIN BOOKING MANAGEMENT
        // ==============================================
        Route::prefix('bookings')->group(function () {
            Route::get('/', [AdminBookingController::class, 'index']);
            Route::get('/{id}', [AdminBookingController::class, 'show']);
            Route::put('/{id}/cancel', [AdminBookingController::class, 'cancel']);
            Route::get('/statistics/overview', [AdminBookingController::class, 'statistics']);
        });

        // ==============================================
        // MODULE 5: ADMIN REVIEW MANAGEMENT
        // ==============================================
        Route::prefix('reviews')->group(function () {
            Route::get('/', [AdminReviewController::class, 'index']);
            Route::get('/pending', [AdminReviewController::class, 'pending']);
            Route::get('/{id}', [AdminReviewController::class, 'show']);
            Route::put('/{id}/approve', [AdminReviewController::class, 'approve']);
            Route::put('/{id}/reject', [AdminReviewController::class, 'reject']);
            Route::delete('/{id}', [AdminReviewController::class, 'destroy']);
            Route::get('/statistics/overview', [AdminReviewController::class, 'statistics']);
        });

        // ==============================================
        // MODULE 6: ADMIN PAYMENT MANAGEMENT
        // ==============================================
        Route::prefix('payments')->group(function () {
            Route::get('/', [AdminPaymentController::class, 'index']);
            Route::get('/{id}', [AdminPaymentController::class, 'show']);
            Route::post('/{id}/refund', [AdminPaymentController::class, 'processRefund']);
            
            // Payouts
            Route::get('/payouts/pending', [AdminPaymentController::class, 'pendingPayouts']);
            Route::get('/payouts/{id}', [AdminPaymentController::class, 'showPayout']);
            Route::put('/payouts/{id}/approve', [AdminPaymentController::class, 'approvePayout']);
            Route::put('/payouts/{id}/reject', [AdminPaymentController::class, 'rejectPayout']);
            
            // Statistics
            Route::get('/statistics/overview', [AdminPaymentController::class, 'statistics']);
        });

        // ==============================================
        // MODULE 7: ADMIN MESSAGING MANAGEMENT
        // ==============================================
        Route::prefix('messages')->group(function () {
            Route::get('/flagged', [AdminMessageController::class, 'flagged']);
            Route::get('/{id}', [AdminMessageController::class, 'show']);
            Route::put('/{id}/resolve', [AdminMessageController::class, 'resolveFlagged']);
            Route::delete('/{id}', [AdminMessageController::class, 'destroy']);
        });

        // ==============================================
        // MODULE 8: ADMIN NOTIFICATION MANAGEMENT
        // ==============================================
        Route::prefix('notifications')->group(function () {
            Route::post('/send', [AdminNotificationController::class, 'send']);
            Route::post('/broadcast', [AdminNotificationController::class, 'broadcast']);
            Route::get('/history', [AdminNotificationController::class, 'history']);
            Route::get('/statistics', [AdminNotificationController::class, 'statistics']);
        });

        // ==============================================
        // MODULE 9: ADMIN SUBSCRIPTION MANAGEMENT
        // ==============================================
        Route::prefix('subscriptions')->group(function () {
            
            // Plans Management
            Route::get('/plans', [AdminSubscriptionController::class, 'plans']);
            Route::get('/plan/{id}', [AdminSubscriptionController::class, 'planById']);
            Route::post('/plans', [AdminSubscriptionController::class, 'createPlan']);
            Route::put('/plans/{id}', [AdminSubscriptionController::class, 'updatePlan']);
            Route::delete('/plans/{id}', [AdminSubscriptionController::class, 'deletePlan']);
            
            // Plan Features
            Route::post('/plans/{id}/features', [AdminSubscriptionController::class, 'addFeature']);
            Route::delete('/plans/{planId}/features/{featureId}', [AdminSubscriptionController::class, 'removeFeature']);
            
            // Subscriptions Management
            Route::get('/', [AdminSubscriptionController::class, 'subscriptions']);
            Route::get('/statistics', [AdminSubscriptionController::class, 'statistics']);
            Route::post('/{id}/cancel', [AdminSubscriptionController::class, 'cancelSubscription']);
        });

        // ==============================================
        // MODULE 10: ADMIN ANALYTICS & REPORTS
        // ==============================================
        Route::prefix('analytics')->group(function () {
            
            // Dashboard
            Route::get('/dashboard', [AdminAnalyticsController::class, 'dashboard']);
            
            // Analytics
            Route::get('/users', [AdminAnalyticsController::class, 'userAnalytics']);
            Route::get('/revenue', [AdminAnalyticsController::class, 'revenueAnalytics']);
            Route::get('/bookings', [AdminAnalyticsController::class, 'bookingAnalytics']);
            Route::get('/reviews', [AdminAnalyticsController::class, 'reviewAnalytics']);
            Route::get('/providers', [AdminAnalyticsController::class, 'providerAnalytics']);
            
            // Export Reports
            Route::get('/export/bookings', [AdminAnalyticsController::class, 'exportBookings']);
            Route::get('/export/payments', [AdminAnalyticsController::class, 'exportPayments']);
            Route::get('/export/reviews', [AdminAnalyticsController::class, 'exportReviews']);
            Route::get('/export/users', [AdminAnalyticsController::class, 'exportUsers']);
            Route::get('/export/revenue-summary', [AdminAnalyticsController::class, 'exportRevenueSummary']);
        });

    });

    // ==============================================
    // WEBHOOK ROUTES (Public - Stripe Verification)
    // ==============================================
    Route::post('/webhooks/stripe', [PaymentController::class, 'handleWebhook']);

});