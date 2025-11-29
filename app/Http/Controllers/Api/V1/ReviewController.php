<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Http\Requests\ReviewResponseRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews (filterable by provider, listing, rating)
     */
    public function index(Request $request)
    {
        $query = Review::with(['client', 'provider', 'listing.category', 'booking'])
            ->approved();

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->byProvider($request->provider_id);
        }

        // Filter by listing
        if ($request->has('listing_id')) {
            $query->byListing($request->listing_id);
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->withRating($request->min_rating);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 10);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Get my reviews (as client or provider)
     */
    public function myReviews(Request $request)
    {
        $user = $request->user();
        
        $query = Review::with(['client', 'provider', 'listing.category', 'booking']);

        if ($user->isClient()) {
            $query->byClient($user->id);
        } elseif ($user->isProvider()) {
            $query->byProvider($user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 10);
        $reviews = $query->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * View single review
     */
    public function show(Request $request, $id)
    {
        $review = Review::with(['client', 'provider', 'listing.category', 'booking'])
            ->findOrFail($id);

        return new ReviewResource($review);
    }

    /**
     * Create review (clients only)
     */
    public function store(ReviewStoreRequest $request)
    {
        $booking = Booking::findOrFail($request->booking_id);

        $review = Review::create([
            'booking_id' => $booking->id,
            'client_id' => $request->user()->id,
            'provider_id' => $booking->provider_id,
            'listing_id' => $booking->listing_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'approved', // Auto-approve, or set to 'pending' if you want moderation
        ]);

        return new ReviewResource($review);
    }

    /**
     * Update review (client only, within 24 hours)
     */
    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $review->update($request->validated());

        return new ReviewResource($review);
    }

    /**
     * Delete review (client only, within 48 hours)
     */
    public function destroy(Request $request, Review $review)
    {
        // Check authorization
        if ($request->user()->id !== $review->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this review.',
            ], 403);
        }

        if (!$review->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Reviews can only be deleted within 48 hours of creation.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * Add provider response to review
     */
    public function addResponse(ReviewResponseRequest $request, Review $review)
    {
        $review->update([
            'provider_response' => $request->response,
            'response_date' => now(),
        ]);

        return new ReviewResource($review);
    }

    /**
     * Update provider response
     */
    public function updateResponse(ReviewResponseRequest $request, Review $review)
    {
        // Check if response exists
        if (!$review->hasResponse()) {
            return response()->json([
                'success' => false,
                'message' => 'No response exists to update.',
            ], 404);
        }

        $review->update([
            'provider_response' => $request->response,
            'response_date' => now(),
        ]);

        return new ReviewResource($review);
    }

    /**
     * Delete provider response
     */
    public function deleteResponse(Request $request, Review $review)
    {
        // Check authorization
        if ($request->user()->id !== $review->provider_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this response.',
            ], 403);
        }

        if (!$review->hasResponse()) {
            return response()->json([
                'success' => false,
                'message' => 'No response exists to delete.',
            ], 404);
        }

        $review->update([
            'provider_response' => null,
            'response_date' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response deleted successfully.',
        ]);
    }

    /**
     * Flag review as inappropriate
     */
    public function flag(Request $request, Review $review)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $review->update([
            'is_flagged' => true,
            'flag_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review has been flagged for moderation.',
        ]);
    }

    /**
     * Mark review as helpful
     */
    public function markHelpful(Request $request, Review $review)
    {
        $review->increment('helpful_count');

        return response()->json([
            'success' => true,
            'helpful_count' => $review->helpful_count,
        ]);
    }

    /**
     * Get review statistics for current user
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        if ($user->isClient()) {
            // Client statistics
            $stats = [
                'total_reviews' => Review::byClient($user->id)->count(),
                'pending' => Review::byClient($user->id)->pending()->count(),
                'approved' => Review::byClient($user->id)->approved()->count(),
                'rejected' => Review::byClient($user->id)->rejected()->count(),
                'average_rating_given' => round(Review::byClient($user->id)->avg('rating'), 2),
            ];
        } elseif ($user->isProvider()) {
            // Provider statistics
            $stats = [
                'total_reviews' => Review::byProvider($user->id)->approved()->count(),
                'average_rating' => round(Review::byProvider($user->id)->approved()->avg('rating'), 2),
                'rating_breakdown' => [
                    '5_star' => Review::byProvider($user->id)->approved()->where('rating', 5)->count(),
                    '4_star' => Review::byProvider($user->id)->approved()->where('rating', 4)->count(),
                    '3_star' => Review::byProvider($user->id)->approved()->where('rating', 3)->count(),
                    '2_star' => Review::byProvider($user->id)->approved()->where('rating', 2)->count(),
                    '1_star' => Review::byProvider($user->id)->approved()->where('rating', 1)->count(),
                ],
                'with_response' => Review::byProvider($user->id)->approved()->whereNotNull('provider_response')->count(),
                'without_response' => Review::byProvider($user->id)->approved()->whereNull('provider_response')->count(),
            ];
        } else {
            $stats = [];
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}