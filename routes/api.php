<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;
use App\Http\Controllers\Api\V1\BookingController;

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
            Route::get('/', [BookingController::class, 'index']); // Get my bookings
            Route::get('/statistics', [BookingController::class, 'statistics']); // Get booking stats
            Route::get('/{id}', [BookingController::class, 'show']); // View specific booking
            Route::post('/', [BookingController::class, 'store']); // Create booking (clients only)
            Route::put('/{id}/accept', [BookingController::class, 'accept']); // Accept booking (providers)
            Route::put('/{id}/reject', [BookingController::class, 'reject']); // Reject booking (providers)
            Route::put('/{id}/cancel', [BookingController::class, 'cancel']); // Cancel booking
            Route::put('/{id}/in-progress', [BookingController::class, 'markInProgress']); // Mark in progress
            Route::put('/{id}/complete', [BookingController::class, 'markCompleted']); // Mark completed
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
                Route::put('/{id}/approve', [AdminListingController::class, 'approve']);
                Route::put('/{id}/reject', [AdminListingController::class, 'reject']);
                Route::put('/{id}/suspend', [AdminListingController::class, 'suspend']);
                Route::delete('/{id}', [AdminListingController::class, 'destroy']);
                Route::put('/{id}/feature', [AdminListingController::class, 'feature']);
            });

            // Booking Management
            Route::prefix('bookings')->group(function () {
                Route::get('/', [AdminBookingController::class, 'index']); // All bookings with filters
                Route::get('/statistics', [AdminBookingController::class, 'statistics']); // Booking analytics
                Route::get('/{id}', [AdminBookingController::class, 'show']); // View specific booking
                Route::put('/{id}/cancel', [AdminBookingController::class, 'cancel']); // Cancel booking
                Route::delete('/{id}', [AdminBookingController::class, 'destroy']); // Delete permanently
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