<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\JobController;
use App\Http\Controllers\Api\V1\BidController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminPayoutController;
use App\Http\Controllers\Api\V1\Admin\AdminMessageController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminJobApplicationController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\Admin\AdminCmsController;
use App\Http\Controllers\Api\V1\Admin\AdminSliderController;
use App\Http\Controllers\Api\V1\Admin\AdminAnnouncementController;
use App\Http\Controllers\Api\V1\Admin\AdminPageController;
use App\Http\Controllers\Api\V1\Admin\AdminSeoController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\Mobile\MobileApiController;
use App\Http\Controllers\Api\V1\JobApplicationController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\User\W9FormController;
use App\Http\Controllers\Api\V1\Admin\AdminInquiryController;
use App\Http\Controllers\Api\V1\Admin\AdminW9FormController;

use App\Http\Middleware\ResponseCompressionMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Care Platform API Routes
| Total Endpoints: 287+ routes across 12 modules
|
*/

// Health check route (outside v1 prefix)
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});

// ============================================================
// API VERSION 1
// ============================================================

Route::prefix('v1')->group(function () {


    // ========================================================
    // PUBLIC ROUTES (no authentication)
    // ========================================================
    
    // Inquiries (Contact Us)
    Route::post('/inquiries', [InquiryController::class, 'store']);
    
    // W-9 Form (Download Blank) - making public for ease of access, or could be protected
    Route::get('/w9-form/download', [W9FormController::class, 'downloadBlankForm']);

    Route::get('/job-applications', [JobApplicationController::class, 'index']);
    Route::post('/job-applications', [JobApplicationController::class, 'store']);
    
    // ========================================================
    // MODULE 12: CMS & PAGES MANAGEMENT (37 endpoints)
    // ========================================================
    
    // Public CMS Routes (10 endpoints - no authentication)
    Route::prefix('cms')->group(function () {
        Route::get('/sliders', [CmsController::class, 'getSliders']);
        Route::get('/announcement', [CmsController::class, 'getAnnouncement']);
        Route::get('/settings', [CmsController::class, 'getSettings']);
        Route::get('/header', [CmsController::class, 'getHeader']);
        Route::get('/footer', [CmsController::class, 'getFooter']);
        Route::get('/config', [CmsController::class, 'getFrontendConfig']); // All-in-one
        Route::get('/pages', [CmsController::class, 'getPages']);
        Route::get('/pages/all', [CmsController::class, 'getAllPages']);
        Route::get('/pages/menu', [CmsController::class, 'getMenuPages']);
        Route::get('/pages/{slug}', [CmsController::class, 'getPage']);
        Route::get('/seo/{pageType}', [CmsController::class, 'getSeo']);
    });
    
    // ========================================================
    // MODULE 1: AUTHENTICATION (11 endpoints)
    // ========================================================
    
    Route::prefix('auth')->group(function () {
        // Public authentication routes
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        
        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
        });
    });

    // ========================================================
    // MODULE 3: PUBLIC CATEGORIES & LISTINGS (8 endpoints)
    // ========================================================
    
    // Public Categories (no auth required)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
    });

    // Public Listings (no auth required)
    Route::prefix('listings')->group(function () {
        Route::get('/', [ListingController::class, 'index']);
        Route::get('/featured', [ListingController::class, 'featured']);
        Route::get('/{id}', [ListingController::class, 'show']);
        Route::get('/{id}/reviews', [ReviewController::class, 'listingReviews']);
    });

    // ========================================================
    // MODULE 5: PUBLIC REVIEWS (2 endpoints)
    // ========================================================
    
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('/{id}', [ReviewController::class, 'show']);
    });

    // ========================================================
    // MODULE 9: PUBLIC SUBSCRIPTION PLANS (1 endpoint)
    // ========================================================
    
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);

    // ========================================================
    // MODULE 6: STRIPE WEBHOOK (1 endpoint)
    // ========================================================
    
    Route::post('/webhooks/stripe', [PaymentController::class, 'webhook']);

    // ========================================================
    // MODULE 12: PUBLIC JOBS (for providers to browse)
    // ========================================================

    Route::prefix('jobs')->group(function () {
        Route::get('/', [JobController::class, 'index']);
        Route::get('/{id}', [JobController::class, 'show']);
    });

    // ========================================================
    // PROTECTED ROUTES (require authentication)
    // ========================================================
    
    Route::middleware('auth:sanctum')->group(function () {
        
        // ====================================================
        // MODULE 2: USER PROFILE (9 endpoints)
        // ====================================================
        
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/photo', [ProfileController::class, 'uploadPhoto']);
            Route::delete('/photo', [ProfileController::class, 'deletePhoto']);
            Route::post('/documents', [ProfileController::class, 'uploadDocument']);
            Route::get('/documents', [ProfileController::class, 'getDocuments']);
            Route::delete('/documents/{id}', [ProfileController::class, 'deleteDocument']);
            Route::get('/verification-status', [ProfileController::class, 'verificationStatus']);
            Route::put('/settings', [ProfileController::class, 'updateSettings']);
            
            // W-9 Form (Upload/Status)
            Route::post('/w9-form', [W9FormController::class, 'uploadFilledForm']);
            Route::get('/w9-form/status', [W9FormController::class, 'status']);
        });

        // ====================================================
        // MODULE 3: PROVIDER LISTINGS (10 endpoints)
        // ====================================================
        
        Route::prefix('provider/listings')->group(function () {
            Route::get('/', [ListingController::class, 'myListings']);
            Route::post('/', [ListingController::class, 'store']);
            Route::put('/{id}', [ListingController::class, 'update']);
            Route::delete('/{id}', [ListingController::class, 'destroy']);
            Route::put('/{id}/toggle-availability', [ListingController::class, 'toggleAvailability']);
            Route::put('/{id}/toggle-status', [ListingController::class, 'toggleStatus']);
            Route::put('/{id}/toggle-featured', [ListingController::class, 'toggleFeatured']);
            Route::post('/{id}/images', [ListingController::class, 'uploadImages']);
            Route::delete('/{listingId}/images/{imageId}', [ListingController::class, 'deleteImage']);
        });

        // ====================================================
        // MODULE 4: BOOKINGS (15 endpoints)
        // ====================================================
        
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::get('/statistics', [BookingController::class, 'statistics']);
            Route::get('/{id}', [BookingController::class, 'show']);
            Route::post('/', [BookingController::class, 'store']);
            Route::put('/{id}', [BookingController::class, 'update']);
            Route::put('/{id}/accept', [BookingController::class, 'accept']);
            Route::put('/{id}/reject', [BookingController::class, 'reject']);
            Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
            Route::put('/{id}/in-progress', [BookingController::class, 'markInProgress']);
            Route::put('/{id}/complete', [BookingController::class, 'markCompleted']);
            Route::put('/{id}/start', [BookingController::class, 'start']);
            Route::delete('/{id}', [BookingController::class, 'destroy']);
            Route::get('/provider/pending', [BookingController::class, 'providerPending']);
            Route::get('/provider/upcoming', [BookingController::class, 'providerUpcoming']);
            Route::get('/client/upcoming', [BookingController::class, 'clientUpcoming']);
        });

        // ====================================================
        // MODULE: CLIENT JOBS & BIDS
        // ====================================================

        Route::prefix('jobs')->group(function () {
            Route::post('/', [JobController::class, 'store']); // Post a new job (Client)
            Route::post('/{id}/bids', [BidController::class, 'store']); // Place a bid (Provider)
            Route::get('/{id}/bids', [BidController::class, 'index']); // Get bids (Client)
        });

        Route::prefix('bids')->group(function () {
            Route::post('/{id}/accept', [BidController::class, 'accept']); // Accept a bid (Client)
        });

        Route::get('/my-bids', [BidController::class, 'myBids']);
        Route::get('/my-jobs', [JobController::class, 'myJobs']);

        // ====================================================
        // MODULE 5: REVIEWS (11 endpoints)
        // ====================================================
        
        Route::prefix('reviews')->group(function () {
            Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
            Route::get('/statistics', [ReviewController::class, 'statistics']);
            Route::post('/', [ReviewController::class, 'store']);
            Route::put('/{review}', [ReviewController::class, 'update']);
            Route::delete('/{review}', [ReviewController::class, 'destroy']);
            
            // Provider Response Routes
            Route::post('/{review}/response', [ReviewController::class, 'addResponse']);
            Route::put('/{review}/response', [ReviewController::class, 'updateResponse']);
            Route::delete('/{review}/response', [ReviewController::class, 'deleteResponse']);
            
            // Engagement Routes
            Route::post('/{review}/flag', [ReviewController::class, 'flag']);
            Route::post('/{review}/helpful', [ReviewController::class, 'markHelpful']);
            
            // My Reviews
            Route::get('/my/given', [ReviewController::class, 'myGivenReviews']);
            Route::get('/my/received', [ReviewController::class, 'myReceivedReviews']);
        });

        // ====================================================
        // MODULE 6: PAYMENTS (12 endpoints)
        // ====================================================
        
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'myPayments']);
            Route::get('/statistics', [PaymentController::class, 'statistics']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::post('/create-intent', [PaymentController::class, 'createPaymentIntent']);
            Route::post('/{id}/confirm', [PaymentController::class, 'confirmPayment']);
            Route::post('/{id}/refund', [PaymentController::class, 'requestRefund']);
            Route::post('/', [PaymentController::class, 'store']);
        });

        // Payouts (Provider Withdrawals) - 5 endpoints
        Route::prefix('payouts')->group(function () {
            Route::get('/balance', [PayoutController::class, 'getBalance']);
            Route::get('/', [PayoutController::class, 'myPayouts']);
            Route::get('/{id}', [PayoutController::class, 'show']);
            Route::post('/request', [PayoutController::class, 'requestPayout']);
            Route::post('/{id}/cancel', [PayoutController::class, 'cancel']);
        });

        // Transactions (1 endpoint)
        Route::prefix('transactions')->group(function () {
            Route::get('/', [PaymentController::class, 'myTransactions']);
        });

        // ====================================================
        // MODULE 7: MESSAGING (11 endpoints)
        // ====================================================
        
        Route::prefix('messages')->group(function () {
            Route::get('/conversations', [MessageController::class, 'conversations']);
            Route::get('/conversations/{id}/messages', [MessageController::class, 'messages']);
            Route::post('/send', [MessageController::class, 'send']);
            Route::put('/conversations/{id}/read', [MessageController::class, 'markAsRead']);
            Route::delete('/{id}', [MessageController::class, 'deleteMessage']);
            Route::put('/conversations/{id}/block', [MessageController::class, 'blockConversation']);
            Route::put('/conversations/{id}/unblock', [MessageController::class, 'unblockConversation']);
            Route::post('/{id}/flag', [MessageController::class, 'flagMessage']);
            Route::get('/unread-count', [MessageController::class, 'unreadCount']);
            Route::get('/search', [MessageController::class, 'search']);
        });

        // Conversations routes merged into 'messages' group above


        // ====================================================
        // MODULE 8: NOTIFICATIONS (15 endpoints)
        // ====================================================
        
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::get('/recent', [NotificationController::class, 'recent']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/{id}/unread', [NotificationController::class, 'markAsUnread']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/', [NotificationController::class, 'deleteAll']);
            Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
            
            // Preferences
            Route::get('/preferences', [NotificationController::class, 'getPreferences']);
            Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
            Route::put('/settings', [NotificationController::class, 'updateSettings']);
            
            // Device Registration
            Route::post('/register-device', [NotificationController::class, 'registerDevice']);
            Route::post('/unregister-device', [NotificationController::class, 'unregisterDevice']);
            
            // Unread Count (Alternative path)
            Route::get('/unread/count', [NotificationController::class, 'unreadCount']);
        });

        // ====================================================
        // MODULE 9: SUBSCRIPTIONS (8 endpoints)
        // ====================================================
        
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

        // ====================================================
        // MODULE 10: USER ANALYTICS (6 endpoints)
        // ====================================================
        
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

        // ====================================================
        // MODULE 11: MOBILE API (9 endpoints)
        // ====================================================
        
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

        // ====================================================
        // ADMIN ROUTES (requires auth:sanctum + check.admin)
        // ====================================================
        
        Route::prefix('admin')->middleware('check.admin')->group(function () {

            Route::get('/job-applications', [AdminJobApplicationController::class, 'index']);

            
            // ================================================
            // ADMIN DASHBOARD (1 endpoint)
            // ================================================
            
            Route::get('/dashboard', [AdminUserController::class, 'dashboard']);
            
            // ================================================
            // ADMIN USER MANAGEMENT (11 endpoints)
            // ================================================
            
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::get('/{id}', [AdminUserController::class, 'show']);
                Route::put('/{id}', [AdminUserController::class, 'update']);
                Route::delete('/{id}', [AdminUserController::class, 'destroy']);
                Route::put('/{id}/verify', [AdminUserController::class, 'verifyUser']);
                Route::put('/{id}/suspend', [AdminUserController::class, 'suspendUser']);
                Route::put('/{id}/activate', [AdminUserController::class, 'activateUser']);
                Route::post('/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
                Route::put('/{id}/ban', [AdminUserController::class, 'ban']);
                Route::put('/{id}/unban', [AdminUserController::class, 'unban']);
                Route::get('/statistics/overview', [AdminUserController::class, 'statistics']);
            });

            // ================================================
            // ADMIN DOCUMENT VERIFICATION (6 endpoints)
            // ================================================
            
            Route::prefix('documents')->group(function () {
                Route::get('/pending', [AdminDocumentController::class, 'getPendingDocuments']);
                Route::get('/', [AdminDocumentController::class, 'getAllDocuments']);
                Route::get('/{id}', [AdminDocumentController::class, 'show']);
                Route::get('/{id}/download', [AdminDocumentController::class, 'download']);
                Route::put('/{id}/approve', [AdminDocumentController::class, 'approve']);
                Route::put('/{id}/reject', [AdminDocumentController::class, 'reject']);
                Route::delete('/{id}', [AdminDocumentController::class, 'destroy']);
            });

            // ================================================
            // ADMIN LISTING MANAGEMENT (8 endpoints)
            // ================================================
            
            Route::prefix('listings')->group(function () {
                Route::get('/', [AdminListingController::class, 'index']);
                Route::get('/pending', [AdminListingController::class, 'getPendingListings']);
                Route::get('/{id}', [AdminListingController::class, 'show']);
                Route::put('/{id}', [AdminListingController::class, 'update']);
                Route::put('/{id}/approve', [AdminListingController::class, 'approve']);
                Route::put('/{id}/reject', [AdminListingController::class, 'reject']);
                Route::put('/{id}/suspend', [AdminListingController::class, 'suspend']);
                Route::delete('/{id}', [AdminListingController::class, 'destroy']);
                Route::put('/{id}/feature', [AdminListingController::class, 'feature']);
            });

            // ================================================
            // ADMIN BOOKING MANAGEMENT (5 endpoints)
            // ================================================
            
            Route::prefix('bookings')->group(function () {
                Route::get('/', [AdminBookingController::class, 'index']);
                Route::get('/statistics', [AdminBookingController::class, 'statistics']);
                Route::get('/{id}', [AdminBookingController::class, 'show']);
                Route::put('/{id}/cancel', [AdminBookingController::class, 'cancel']);
                Route::delete('/{id}', [AdminBookingController::class, 'destroy']);
                Route::get('/statistics/overview', [AdminBookingController::class, 'statistics']);
            });

            // ================================================
            // ADMIN REVIEW MANAGEMENT (9 endpoints)
            // ================================================
            
            Route::prefix('reviews')->group(function () {
                Route::get('/', [AdminReviewController::class, 'index']);
                Route::get('/pending', [AdminReviewController::class, 'pending']);
                Route::get('/flagged', [AdminReviewController::class, 'flagged']);
                Route::get('/statistics', [AdminReviewController::class, 'statistics']);
                Route::get('/{id}', [AdminReviewController::class, 'show']);
                Route::put('/{id}/approve', [AdminReviewController::class, 'approve']);
                Route::put('/{id}/reject', [AdminReviewController::class, 'reject']);
                Route::put('/{id}/unflag', [AdminReviewController::class, 'unflag']);
                Route::delete('/{id}', [AdminReviewController::class, 'destroy']);
                Route::get('/statistics/overview', [AdminReviewController::class, 'statistics']);
            });

            // ================================================
            // ADMIN PAYMENT MANAGEMENT (13 endpoints)
            // ================================================
            
            Route::prefix('payments')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'index']);
                Route::get('/statistics', [AdminPaymentController::class, 'statistics']);
                Route::get('/{id}', [AdminPaymentController::class, 'show']);
                Route::post('/{id}/refund', [AdminPaymentController::class, 'refund']);
                Route::post('/{id}/process-refund', [AdminPaymentController::class, 'processRefund']);
            });

            // ================================================
            // ADMIN PAYOUT MANAGEMENT (8 endpoints)
            // ================================================
            
            Route::prefix('payouts')->group(function () {
                Route::get('/', [AdminPayoutController::class, 'index']);
                Route::get('/statistics', [AdminPayoutController::class, 'statistics']);
                Route::get('/{id}', [AdminPayoutController::class, 'show']);
                Route::post('/{id}/approve', [AdminPayoutController::class, 'approvePayout']);
                Route::post('/{id}/reject', [AdminPayoutController::class, 'rejectPayout']);
                Route::post('/bulk-approve', [AdminPayoutController::class, 'bulkApprove']);
            });

            // Transaction Management (1 endpoint)
            Route::prefix('transactions')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'transactions']);
            });

            // ================================================
            // ADMIN MESSAGE MANAGEMENT (10 endpoints)
            // ================================================
            
            Route::prefix('messages')->group(function () {
                Route::get('/conversations', [AdminMessageController::class, 'conversations']);
                Route::get('/', [AdminMessageController::class, 'messages']);
                Route::get('/flagged', [AdminMessageController::class, 'flaggedMessages']);
                Route::get('/statistics', [AdminMessageController::class, 'statistics']);
                Route::get('/{id}', [AdminMessageController::class, 'showMessage']);
                Route::delete('/{id}', [AdminMessageController::class, 'deleteMessage']);
                Route::put('/{id}/unflag', [AdminMessageController::class, 'unflagMessage']);
                Route::put('/conversations/{id}/block', [AdminMessageController::class, 'blockConversation']);
                Route::put('/conversations/{id}/unblock', [AdminMessageController::class, 'unblockConversation']);
                Route::put('/{id}/resolve', [AdminMessageController::class, 'resolveFlagged']);
            });

            // ================================================
            // ADMIN NOTIFICATION MANAGEMENT (9 endpoints)
            // ================================================
            
            Route::prefix('notifications')->group(function () {
                Route::get('/', [AdminNotificationController::class, 'index']);
                Route::get('/statistics', [AdminNotificationController::class, 'statistics']);
                Route::post('/announcement', [AdminNotificationController::class, 'sendAnnouncement']);
                Route::post('/send-to-users', [AdminNotificationController::class, 'sendToUsers']);
                Route::post('/test', [AdminNotificationController::class, 'test']);
                Route::delete('/{id}', [AdminNotificationController::class, 'destroy']);
                Route::post('/send', [AdminNotificationController::class, 'send']);
                Route::post('/broadcast', [AdminNotificationController::class, 'broadcast']);
                Route::get('/history', [AdminNotificationController::class, 'history']);
            });

            // ================================================
            // ADMIN SUBSCRIPTION MANAGEMENT (10 endpoints)
            // ================================================
            
            Route::prefix('subscriptions')->group(function () {
                Route::get('/plans', [AdminSubscriptionController::class, 'plans']);
                Route::get('/plan/{id}', [AdminSubscriptionController::class, 'planById']);
                Route::post('/plans', [AdminSubscriptionController::class, 'createPlan']);
                Route::put('/plans/{id}', [AdminSubscriptionController::class, 'updatePlan']);
                Route::delete('/plans/{id}', [AdminSubscriptionController::class, 'deletePlan']);
                Route::post('/plans/{id}/features', [AdminSubscriptionController::class, 'addFeature']);
                Route::delete('/plans/{planId}/features/{featureId}', [AdminSubscriptionController::class, 'removeFeature']);
                Route::get('/', [AdminSubscriptionController::class, 'subscriptions']);
                Route::get('/statistics', [AdminSubscriptionController::class, 'statistics']);
                Route::post('/{id}/cancel', [AdminSubscriptionController::class, 'cancelSubscription']);
            });

            // ================================================
            // ADMIN ANALYTICS & REPORTS (11 endpoints)
            // ================================================
            
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

            // ================================================
            // ADMIN INQUIRIES MANAGEMENT (4 endpoints)
            // ================================================

            Route::prefix('inquiries')->group(function () {
                Route::get('/', [AdminInquiryController::class, 'index']);
                Route::get('/{id}', [AdminInquiryController::class, 'show']);
                Route::post('/{id}/reply', [AdminInquiryController::class, 'reply']);
                Route::delete('/{id}', [AdminInquiryController::class, 'destroy']);
            });

            // ================================================
            // ADMIN W-9 FORM MANAGEMENT (6 endpoints)
            // ================================================

            Route::prefix('w9-forms')->group(function () {
                Route::get('/statistics', [AdminW9FormController::class, 'statistics']);
                Route::get('/pending', [AdminW9FormController::class, 'pending']);
                Route::get('/', [AdminW9FormController::class, 'index']);
                Route::get('/{id}', [AdminW9FormController::class, 'show']);
                Route::put('/{id}/approve', [AdminW9FormController::class, 'approve']);
                Route::put('/{id}/reject', [AdminW9FormController::class, 'reject']);
            });

            // ================================================
            // ADMIN CMS MANAGEMENT (27 endpoints)
            // ================================================
            
            Route::prefix('cms')->group(function () {
                
                // Site Settings (9)
                Route::get('/settings', [AdminCmsController::class, 'getSettings']);
                Route::get('/settings/{group}', [AdminCmsController::class, 'getSettingsByGroup']);
                Route::post('/settings', [AdminCmsController::class, 'updateSettings']);
                Route::put('/settings/single', [AdminCmsController::class, 'updateSetting']);
                Route::post('/settings/clear-cache', [AdminCmsController::class, 'clearCache']);
                Route::get('/header/menu', [AdminCmsController::class, 'getHeaderMenu']);
                Route::put('/header/menu', [AdminCmsController::class, 'updateHeaderMenu']);
                Route::get('/footer/links', [AdminCmsController::class, 'getFooterLinks']);
                Route::put('/footer/links', [AdminCmsController::class, 'updateFooterLinks']);
                
                // Sliders (5)
                Route::get('/sliders', [AdminSliderController::class, 'index']);
                Route::get('/sliders/{id}', [AdminSliderController::class, 'show']);
                Route::post('/sliders', [AdminSliderController::class, 'store']);
                Route::put('/sliders/{id}', [AdminSliderController::class, 'update']);
                Route::delete('/sliders/{id}', [AdminSliderController::class, 'destroy']);
                Route::post('/sliders/reorder', [AdminSliderController::class, 'reorder']);
                
                // Announcements (5)
                Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
                Route::get('/announcements/{id}', [AdminAnnouncementController::class, 'show']);
                Route::get('/announcements/current', [AdminAnnouncementController::class, 'getCurrent']);
                Route::post('/announcements', [AdminAnnouncementController::class, 'store']);
                Route::put('/announcements/{id}', [AdminAnnouncementController::class, 'update']);
                Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'destroy']);
                
                // Pages (5)
                Route::get('/pages', [AdminPageController::class, 'index']);
                Route::get('/pages/{id}', [AdminPageController::class, 'show']);
                Route::post('/pages', [AdminPageController::class, 'store']);
                Route::put('/pages/{id}', [AdminPageController::class, 'update']);
                Route::delete('/pages/{id}', [AdminPageController::class, 'destroy']);
                
                // SEO (4)
                Route::get('/seo', [AdminSeoController::class, 'index']);
                Route::get('/seo/{pageType}', [AdminSeoController::class, 'show']);
                Route::put('/seo/{pageType}', [AdminSeoController::class, 'update']);
                Route::post('/seo/clear-cache', [AdminSeoController::class, 'clearCache']);
            });
        });
    });
});