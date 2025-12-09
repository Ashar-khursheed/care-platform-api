<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminMessageController;
use App\Http\Controllers\Api\V1\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriptionController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        
        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });
    });

    // Public Categories & Listings (no auth required)
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('/{slug}', [CategoryController::class, 'show']);
    });

    Route::prefix('listings')->group(function () {
        Route::get('/', [ListingController::class, 'index']);
        Route::get('/featured', [ListingController::class, 'featured']);
        Route::get('/{id}', [ListingController::class, 'show']);
    });

    // Public Reviews (no auth required)
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::get('/{id}', [ReviewController::class, 'show']);
    });

    // Public Subscription Plans (no auth required)
    Route::get('/subscription-plans', [SubscriptionController::class, 'plans']);

    // Stripe Webhook (no auth required)
    Route::post('/webhooks/stripe', [PaymentController::class, 'webhook']);

    // Protected Routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // User Profile Routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::post('/photo', [ProfileController::class, 'uploadPhoto']);
            Route::delete('/photo', [ProfileController::class, 'deletePhoto']);
            Route::post('/documents', [ProfileController::class, 'uploadDocument']);
            Route::get('/documents', [ProfileController::class, 'getDocuments']);
            Route::delete('/documents/{id}', [ProfileController::class, 'deleteDocument']);
            Route::get('/verification-status', [ProfileController::class, 'verificationStatus']);
        });

        // Provider Listing Management Routes
        Route::prefix('my-listings')->group(function () {
            Route::get('/', [ListingController::class, 'myListings']);
            Route::post('/', [ListingController::class, 'store']);
            Route::put('/{id}', [ListingController::class, 'update']);
            Route::delete('/{id}', [ListingController::class, 'destroy']);
            Route::put('/{id}/toggle-availability', [ListingController::class, 'toggleAvailability']);
        });

        // Booking Routes (Clients & Providers)
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::get('/statistics', [BookingController::class, 'statistics']);
            Route::get('/{id}', [BookingController::class, 'show']);
            Route::post('/', [BookingController::class, 'store']);
            Route::put('/{id}/accept', [BookingController::class, 'accept']);
            Route::put('/{id}/reject', [BookingController::class, 'reject']);
            Route::put('/{id}/cancel', [BookingController::class, 'cancel']);
            Route::put('/{id}/in-progress', [BookingController::class, 'markInProgress']);
            Route::put('/{id}/complete', [BookingController::class, 'markCompleted']);
        });

        // Review Routes (Clients & Providers)
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
        });

        // Payment Routes (Clients & Providers)
        Route::prefix('payments')->group(function () {
            Route::get('/', [PaymentController::class, 'myPayments']);
            Route::get('/statistics', [PaymentController::class, 'statistics']);
            Route::get('/{id}', [PaymentController::class, 'show']);
            Route::post('/create-intent', [PaymentController::class, 'createPaymentIntent']);
            Route::post('/{id}/confirm', [PaymentController::class, 'confirmPayment']);
            Route::post('/{id}/refund', [PaymentController::class, 'requestRefund']);
        });

        // Transaction Routes
        Route::prefix('transactions')->group(function () {
            Route::get('/', [PaymentController::class, 'myTransactions']);
        });

        // Messaging Routes (Clients & Providers)
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

        // Notification Routes (All Users)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::get('/recent', [NotificationController::class, 'recent']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/{id}/unread', [NotificationController::class, 'markAsUnread']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/', [NotificationController::class, 'deleteAll']);
            
            // Preferences
            Route::get('/preferences', [NotificationController::class, 'getPreferences']);
            Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
            Route::put('/settings', [NotificationController::class, 'updateSettings']);
            
            // Device Registration
            Route::post('/register-device', [NotificationController::class, 'registerDevice']);
            Route::post('/unregister-device', [NotificationController::class, 'unregisterDevice']);
        });

        // Subscription Routes (All Users)
        Route::prefix('subscriptions')->group(function () {
            Route::get('/current', [SubscriptionController::class, 'current']); // Current subscription
            Route::get('/history', [SubscriptionController::class, 'history']); // Subscription history
            Route::get('/usage', [SubscriptionController::class, 'usage']); // Usage statistics
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe']); // Subscribe to plan
            Route::post('/upgrade', [SubscriptionController::class, 'upgrade']); // Upgrade plan
            Route::post('/downgrade', [SubscriptionController::class, 'downgrade']); // Downgrade plan
            Route::post('/cancel', [SubscriptionController::class, 'cancel']); // Cancel subscription
            Route::post('/resume', [SubscriptionController::class, 'resume']); // Resume subscription
        });

        // Admin Routes (only accessible by admin users)
        Route::prefix('admin')->middleware('check.admin')->group(function () {
            
            // Dashboard & Analytics
            Route::get('/dashboard', [AdminUserController::class, 'dashboard']);
            
            // User Management
            Route::prefix('users')->group(function () {
                Route::get('/', [AdminUserController::class, 'index']);
                Route::get('/{id}', [AdminUserController::class, 'show']);
                Route::put('/{id}', [AdminUserController::class, 'update']);
                Route::delete('/{id}', [AdminUserController::class, 'destroy']);
                Route::put('/{id}/verify', [AdminUserController::class, 'verifyUser']);
                Route::put('/{id}/suspend', [AdminUserController::class, 'suspendUser']);
                Route::put('/{id}/activate', [AdminUserController::class, 'activateUser']);
                Route::post('/{id}/reset-password', [AdminUserController::class, 'resetPassword']);
            });

            // Document Verification Management
            Route::prefix('documents')->group(function () {
                Route::get('/pending', [AdminDocumentController::class, 'getPendingDocuments']);
                Route::get('/', [AdminDocumentController::class, 'getAllDocuments']);
                Route::get('/{id}', [AdminDocumentController::class, 'show']);
                Route::get('/{id}/download', [AdminDocumentController::class, 'download']);
                Route::put('/{id}/approve', [AdminDocumentController::class, 'approve']);
                Route::put('/{id}/reject', [AdminDocumentController::class, 'reject']);
                Route::delete('/{id}', [AdminDocumentController::class, 'destroy']);
            });

            // Listing Management
            Route::prefix('listings')->group(function () {
                Route::get('/', [AdminListingController::class, 'index']);
                Route::get('/pending', [AdminListingController::class, 'getPendingListings']);
                Route::get('/{id}', [AdminListingController::class, 'show']);
                Route::put('/{id}/approve', [AdminListingController::class, 'approve']);
                Route::put('/{id}/reject', [AdminListingController::class, 'reject']);
                Route::put('/{id}/suspend', [AdminListingController::class, 'suspend']);
                Route::delete('/{id}', [AdminListingController::class, 'destroy']);
                Route::put('/{id}/feature', [AdminListingController::class, 'feature']);
            });

            // Booking Management
            Route::prefix('bookings')->group(function () {
                Route::get('/', [AdminBookingController::class, 'index']);
                Route::get('/statistics', [AdminBookingController::class, 'statistics']);
                Route::get('/{id}', [AdminBookingController::class, 'show']);
                Route::put('/{id}/cancel', [AdminBookingController::class, 'cancel']);
                Route::delete('/{id}', [AdminBookingController::class, 'destroy']);
            });

            // Review Management
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
            });

            // Payment Management
            Route::prefix('payments')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'index']);
                Route::get('/statistics', [AdminPaymentController::class, 'statistics']);
                Route::get('/{id}', [AdminPaymentController::class, 'show']);
                Route::post('/{id}/refund', [AdminPaymentController::class, 'refund']);
            });

            // Payout Management
            Route::prefix('payouts')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'payouts']);
                Route::post('/{id}/process', [AdminPaymentController::class, 'processPayout']);
            });

            // Transaction Management
            Route::prefix('transactions')->group(function () {
                Route::get('/', [AdminPaymentController::class, 'transactions']);
            });

            // Message Management
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
            });

            // Notification Management
            Route::prefix('notifications')->group(function () {
                Route::get('/', [AdminNotificationController::class, 'index']);
                Route::get('/statistics', [AdminNotificationController::class, 'statistics']);
                Route::post('/announcement', [AdminNotificationController::class, 'sendAnnouncement']);
                Route::post('/send-to-users', [AdminNotificationController::class, 'sendToUsers']);
                Route::post('/test', [AdminNotificationController::class, 'test']);
                Route::delete('/{id}', [AdminNotificationController::class, 'destroy']);
            });

            // Subscription Management
            Route::prefix('subscriptions')->group(function () {
                Route::get('/plans', [AdminSubscriptionController::class, 'plans']); // All plans
                Route::post('/plans', [AdminSubscriptionController::class, 'createPlan']); // Create plan
                Route::put('/plans/{id}', [AdminSubscriptionController::class, 'updatePlan']); // Update plan
                Route::delete('/plans/{id}', [AdminSubscriptionController::class, 'deletePlan']); // Delete plan
                Route::post('/plans/{id}/features', [AdminSubscriptionController::class, 'addFeature']); // Add feature
                Route::delete('/plans/{planId}/features/{featureId}', [AdminSubscriptionController::class, 'removeFeature']); // Remove feature
                Route::get('/', [AdminSubscriptionController::class, 'subscriptions']); // All subscriptions
                Route::get('/statistics', [AdminSubscriptionController::class, 'statistics']); // Statistics
                Route::post('/{id}/cancel', [AdminSubscriptionController::class, 'cancelSubscription']); // Cancel subscription
            });
        });
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
    ]);
});