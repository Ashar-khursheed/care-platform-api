<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    /**
     * Get all reviews with filters
     */
    public function index(Request $request)
    {
        $query = Review::with(['client', 'provider', 'listing.category', 'booking', 'moderator']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by flagged
        if ($request->has('is_flagged')) {
            $query->where('is_flagged', $request->boolean('is_flagged'));
        }

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->byProvider($request->provider_id);
        }

        // Filter by client
        if ($request->has('client_id')) {
            $query->byClient($request->client_id);
        }

        // Filter by rating
        if ($request->has('min_rating')) {
            $query->withRating($request->min_rating);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Get pending reviews
     */
    public function pending(Request $request)
    {
        $reviews = Review::with(['client', 'provider', 'listing.category', 'booking'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    /**
     * Get flagged reviews
     */
    public function flagged(Request $request)
    {
        $reviews = Review::with(['client', 'provider', 'listing.category', 'booking'])
            ->flagged()
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    /**
     * View single review
     */
    public function show($id)
    {
        $review = Review::with(['client', 'provider', 'listing.category', 'booking', 'moderator'])
            ->findOrFail($id);

        return new ReviewResource($review);
    }

    /**
     * Approve review
     */
    public function approve(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $review->update([
            'status' => 'approved',
            'is_flagged' => false,
            'flag_reason' => null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Reject review
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $review = Review::findOrFail($id);

        $review->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review rejected successfully.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Unflag review
     */
    public function unflag(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $review->update([
            'is_flagged' => false,
            'flag_reason' => null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review unflagged successfully.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Delete review (permanent)
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted permanently.',
        ]);
    }

    /**
     * Get review statistics
     */
    public function statistics()
    {
        $totalReviews = Review::count();
        $approvedReviews = Review::approved()->count();
        $pendingReviews = Review::pending()->count();
        $rejectedReviews = Review::rejected()->count();
        $flaggedReviews = Review::flagged()->count();

        $averageRating = round(Review::approved()->avg('rating'), 2);

        // Rating distribution
        $ratingDistribution = [
            '5_star' => Review::approved()->where('rating', 5)->count(),
            '4_star' => Review::approved()->where('rating', 4)->count(),
            '3_star' => Review::approved()->where('rating', 3)->count(),
            '2_star' => Review::approved()->where('rating', 2)->count(),
            '1_star' => Review::approved()->where('rating', 1)->count(),
        ];

        // Reviews with provider response
        $withResponse = Review::approved()->whereNotNull('provider_response')->count();
        $withoutResponse = Review::approved()->whereNull('provider_response')->count();

        // Recent reviews (last 7 days)
        $recentReviews = Review::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Most reviewed providers
        $topReviewedProviders = Review::approved()
            ->selectRaw('provider_id, COUNT(*) as reviews_count, AVG(rating) as average_rating')
            ->groupBy('provider_id')
            ->orderBy('reviews_count', 'desc')
            ->limit(10)
            ->with('provider')
            ->get()
            ->map(function ($review) {
                return [
                    'provider_id' => $review->provider_id,
                    'provider_name' => $review->provider->first_name . ' ' . $review->provider->last_name,
                    'reviews_count' => $review->reviews_count,
                    'average_rating' => round($review->average_rating, 2),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'approved' => $approvedReviews,
                'pending' => $pendingReviews,
                'rejected' => $rejectedReviews,
                'flagged' => $flaggedReviews,
                'average_rating' => $averageRating,
                'rating_distribution' => $ratingDistribution,
                'with_response' => $withResponse,
                'without_response' => $withoutResponse,
                'recent_reviews' => $recentReviews,
                'top_reviewed_providers' => $topReviewedProviders,
            ],
        ]);
    }
}