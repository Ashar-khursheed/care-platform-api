<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseOptimizer;
use App\Models\ServiceListing;
use App\Models\ServiceCategory;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MobileApiController extends Controller
{
    protected $optimizer;

    public function __construct(ApiResponseOptimizer $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * Get home screen data (cached, optimized)
     */
    public function getHomeData(Request $request)
    {
        $cacheKey = 'mobile_home_data_' . $request->user()->id;

        $data = $this->optimizer->cachedResponse($cacheKey, function () {
            return [
                'featured_listings' => ServiceListing::active()
                    ->where('is_featured', true)
                    ->with('provider:id,first_name,last_name,average_rating', 'category:id,name')
                    ->limit(10)
                    ->get()
                    ->map(function ($listing) {
                        return [
                            'id' => $listing->id,
                            'title' => $listing->title,
                            'description' => \Str::limit($listing->description, 100),
                            'price' => $listing->price_per_hour,
                            'rating' => $listing->average_rating,
                            'image' => $listing->images[0] ?? null,
                            'category' => $listing->category->name,
                            'provider' => [
                                'name' => $listing->provider->first_name . ' ' . $listing->provider->last_name,
                                'rating' => $listing->provider->average_rating,
                            ],
                        ];
                    }),
                
                'categories' => ServiceCategory::select('id', 'name', 'icon', 'slug')
                    ->withCount('listings')
                    ->get(),
                
                'stats' => [
                    'total_providers' => User::where('user_type', 'provider')
                        ->where('is_verified', true)
                        ->count(),
                    'total_services' => ServiceListing::active()->count(),
                ],
            ];
        }, 300); // Cache for 5 minutes

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get listings with infinite scroll
     */
    public function getListings(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:50',
            'category_id' => 'nullable|exists:service_categories,id',
            'search' => 'nullable|string|max:255',
        ]);

        $query = ServiceListing::active()
            ->with('provider:id,first_name,last_name,average_rating', 'category:id,name');

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $listings = $query->paginate($request->get('per_page', 20));

        // Optimize for mobile
        $optimizedListings = $this->optimizer->optimizePaginatedResponse($listings, [
            'simplified' => true,
        ]);

        // Transform data
        $optimizedListings['data'] = collect($optimizedListings['data'])->map(function ($listing) {
            return [
                'id' => $listing->id,
                'title' => $listing->title,
                'description' => \Str::limit($listing->description, 150),
                'price' => $listing->price_per_hour,
                'rating' => $listing->average_rating,
                'reviews_count' => $listing->reviews_count,
                'image' => $listing->images[0] ?? null,
                'category' => $listing->category->name,
                'provider' => [
                    'name' => $listing->provider->first_name . ' ' . $listing->provider->last_name,
                    'rating' => $listing->provider->average_rating,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            ...$optimizedListings,
        ]);
    }

    /**
     * Get listing details (minimal initial load)
     */
    public function getListingDetails(Request $request, $id)
    {
        $listing = ServiceListing::with([
            'provider:id,first_name,last_name,bio,average_rating,reviews_count,profile_photo',
            'category:id,name',
        ])->findOrFail($id);

        $data = [
            'id' => $listing->id,
            'title' => $listing->title,
            'description' => $listing->description,
            'price' => $listing->price_per_hour,
            'rating' => $listing->average_rating,
            'reviews_count' => $listing->reviews_count,
            'images' => $listing->images,
            'location' => [
                'address' => $listing->address,
                'city' => $listing->city,
                'state' => $listing->state,
            ],
            'category' => $listing->category,
            'provider' => [
                'id' => $listing->provider->id,
                'name' => $listing->provider->first_name . ' ' . $listing->provider->last_name,
                'bio' => \Str::limit($listing->provider->bio, 200),
                'rating' => $listing->provider->average_rating,
                'reviews_count' => $listing->provider->reviews_count,
                'photo' => $listing->provider->profile_photo,
            ],
            'features' => $listing->features,
            'is_available' => $listing->is_available,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get listing reviews (separate endpoint for lazy loading)
     */
    public function getListingReviews(Request $request, $id)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:20',
        ]);

        $reviews = \App\Models\Review::where('listing_id', $id)
            ->approved()
            ->with('client:id,first_name,last_name,profile_photo')
            ->latest()
            ->paginate($request->get('per_page', 10));

        $optimizedReviews = $this->optimizer->optimizePaginatedResponse($reviews, [
            'simplified' => true,
        ]);

        // Transform data
        $optimizedReviews['data'] = collect($optimizedReviews['data'])->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'client' => [
                    'name' => $review->client->first_name . ' ' . $review->client->last_name,
                    'photo' => $review->client->profile_photo,
                ],
                'created_at' => $review->created_at->diffForHumans(),
                'provider_response' => $review->provider_response,
            ];
        });

        return response()->json([
            'success' => true,
            ...$optimizedReviews,
        ]);
    }

    /**
     * Get user bookings (lightweight)
     */
    public function getUserBookings(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,accepted,in_progress,completed,canceled',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Booking::where('client_id', $request->user()->id)
            ->with('provider:id,first_name,last_name,profile_photo', 'listing:id,title,images')
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->paginate(15);

        $optimizedBookings = $this->optimizer->optimizePaginatedResponse($bookings, [
            'simplified' => true,
        ]);

        // Transform data
        $optimizedBookings['data'] = collect($optimizedBookings['data'])->map(function ($booking) {
            return [
                'id' => $booking->id,
                'service' => $booking->listing->title,
                'image' => $booking->listing->images[0] ?? null,
                'provider' => [
                    'name' => $booking->provider->first_name . ' ' . $booking->provider->last_name,
                    'photo' => $booking->provider->profile_photo,
                ],
                'date' => $booking->booking_date->format('M d, Y'),
                'time' => $booking->start_time . ' - ' . $booking->end_time,
                'amount' => $booking->total_amount,
                'status' => $booking->status,
            ];
        });

        return response()->json([
            'success' => true,
            ...$optimizedBookings,
        ]);
    }

    /**
     * Get categories (lightweight list)
     */
    public function getCategories()
    {
        $cacheKey = 'mobile_categories';

        $categories = Cache::remember($cacheKey, 3600, function () {
            return ServiceCategory::select('id', 'name', 'slug', 'icon')
                ->withCount('listings')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Search with autocomplete
     */
    public function searchAutocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
        ]);

        $query = $request->query;

        $results = ServiceListing::active()
            ->where('title', 'like', $query . '%')
            ->select('id', 'title')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get notifications (optimized)
     */
    public function getNotifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? '',
                    'message' => $notification->data['message'] ?? '',
                    'read' => !is_null($notification->read_at),
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get app configuration
     */
    public function getAppConfig()
    {
        $cacheKey = 'mobile_app_config';

        $config = Cache::remember($cacheKey, 3600, function () {
            return [
                'version' => '1.0.0',
                'min_app_version' => '1.0.0',
                'force_update' => false,
                'maintenance_mode' => false,
                'features' => [
                    'chat_enabled' => true,
                    'video_call_enabled' => false,
                    'payment_methods' => ['card', 'wallet'],
                ],
                'settings' => [
                    'max_image_size' => 5242880, // 5MB
                    'allowed_image_types' => ['jpg', 'jpeg', 'png'],
                    'pagination_default' => 20,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }
}
