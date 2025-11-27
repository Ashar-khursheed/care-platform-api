<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\AdminListingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ListingController;

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
        Route::get('/', [CategoryController::class, 'index']); // Get all categories
        Route::get('/{slug}', [CategoryController::class, 'show']); // Get category by slug
    });

    Route::prefix('listings')->group(function () {
        Route::get('/', [ListingController::class, 'index']); // Browse all listings
        Route::get('/featured', [ListingController::class, 'featured']); // Get featured listings
        Route::get('/{id}', [ListingController::class, 'show']); // View specific listing
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
            Route::get('/', [ListingController::class, 'myListings']); // Get my listings
            Route::post('/', [ListingController::class, 'store']); // Create listing
            Route::put('/{id}', [ListingController::class, 'update']); // Update listing
            Route::delete('/{id}', [ListingController::class, 'destroy']); // Delete listing
            Route::put('/{id}/toggle-availability', [ListingController::class, 'toggleAvailability']); // Toggle availability
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
                Route::get('/', [AdminListingController::class, 'index']); // All listings with filters
                Route::get('/pending', [AdminListingController::class, 'getPendingListings']); // Pending listings
                Route::put('/{id}/approve', [AdminListingController::class, 'approve']); // Approve listing
                Route::put('/{id}/reject', [AdminListingController::class, 'reject']); // Reject listing
                Route::put('/{id}/suspend', [AdminListingController::class, 'suspend']); // Suspend listing
                Route::delete('/{id}', [AdminListingController::class, 'destroy']); // Delete listing
                Route::put('/{id}/feature', [AdminListingController::class, 'feature']); // Feature listing
            });
        });

        // Booking Routes (Coming in Module 5)
        // Route::prefix('bookings')->group(function () {
        //     Route::get('/', [BookingController::class, 'index']);
        //     Route::post('/', [BookingController::class, 'store']);
        //     Route::get('/{id}', [BookingController::class, 'show']);
        // });
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
    ]);
});