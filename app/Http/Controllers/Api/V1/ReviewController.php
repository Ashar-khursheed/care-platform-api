<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
 use Illuminate\Http\Response;
use App\Http\Requests\ReviewResponseRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: '/api/v1/reviews',
        summary: 'Get all reviews',
        tags: ['Reviews']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
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

    #[OA\Get(
        path: '/api/v1/my-reviews',
        summary: 'Get my reviews',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
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

    #[OA\Get(
        path: '/api/v1/reviews/{id}',
        summary: 'Get review details',
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show(Request $request, $id)
    {
        $review = Review::with(['client', 'provider', 'listing.category', 'booking'])
            ->findOrFail($id);

        return new ReviewResource($review);
    }

    #[OA\Post(
        path: '/api/v1/bookings/{id}/review',
        summary: 'Create review',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 201, description: 'Created')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function store(Request $request)
    {
        // Basic validation
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Find the booking
        $booking = Booking::find($request->booking_id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        }

        // Ensure booking belongs to the client
        if ($booking->client_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'This booking does not belong to you.'
            ], 403);
        }

        // Ensure booking is completed
        if ($booking->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'You can only review completed bookings.'
            ], 400);
        }

        // Check if review already exists
        $existingReview = Review::where('booking_id', $booking->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this booking.'
            ], 400);
        }

        // Create review
        $review = Review::create([
            'booking_id' => $booking->id,
            'client_id' => $request->user()->id,
            'provider_id' => $booking->provider_id,
            'listing_id' => $booking->listing_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'approved', // automatically approved
        ]);

       return response()->json([
            'success' => true,
            'message' => 'Review created successfully.',
            'data' => $review
        ], 201);

    }
    #[OA\Put(
        path: '/api/v1/reviews/{id}',
        summary: 'Update review',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $review->update($request->validated());

        return new ReviewResource($review);
    }

    #[OA\Delete(
        path: '/api/v1/reviews/{id}',
        summary: 'Delete review',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 403, description: 'Unauthorized')]
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

    #[OA\Post(
        path: '/api/v1/reviews/{id}/response',
        summary: 'Add provider response to review',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [new OA\Property(property: 'response', type: 'string')])
    )]
    #[OA\Response(response: 200, description: 'Response added')]
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

    #[OA\Post(
        path: '/api/v1/reviews/{id}/flag',
        summary: 'Flag review as inappropriate',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(properties: [new OA\Property(property: 'reason', type: 'string')])
    )]
    #[OA\Response(response: 200, description: 'Flagged')]
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

    #[OA\Post(
        path: '/api/v1/reviews/{id}/helpful',
        summary: 'Mark review as helpful',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Incremented')]
    public function markHelpful(Request $request, Review $review)
    {
        $review->increment('helpful_count');

        return response()->json([
            'success' => true,
            'helpful_count' => $review->helpful_count,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/reviews/statistics',
        summary: 'Get review statistics',
        security: [['bearerAuth' => []]],
        tags: ['Reviews']
    )]
    #[OA\Response(response: 200, description: 'Success')]
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
