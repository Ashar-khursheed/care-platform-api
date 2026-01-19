<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListingStoreRequest;
use App\Http\Requests\ListingUpdateRequest;
use App\Http\Resources\ListingResource;
use App\Models\ServiceListing;
use Illuminate\Http\Request;

class ListingController extends Controller
{
        /**
 *     @OA\Get(
 *         path="/api/v1/provider/listings",
 *         summary="Get my listings",
 *         tags={"Listings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function myListings(Request $request)
    {
        $query = ServiceListing::with(['category', 'provider'])
            ->where('provider_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $listings = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => [
                'listings' => ListingResource::collection($listings),
                'pagination' => [
                    'total' => $listings->total(),
                    'per_page' => $listings->perPage(),
                    'current_page' => $listings->currentPage(),
                    'last_page' => $listings->lastPage(),
                ]
            ]
        ], 200);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/listings",
 *         summary="Get all listings",
 *         tags={"Listings"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function index(Request $request)
    {
        $query = ServiceListing::with(['category', 'provider'])
            ->active();

        // Filter by user_type 
        // user_type=provider -> Show listings posted by PROVIDERS (FindCare page)
        // user_type=client -> Show listings posted by CLIENTS (Caregivers page)
        if ($request->has('user_type')) {
            $query->whereHas('provider', function($q) use ($request) {
                $q->where('user_type', $request->user_type);
            });
        }
        
        // Filter by provider_id if passed (to see specific provider's listings)
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Filter by minimum rating
        if ($request->has('min_rating')) {
            $query->minRating($request->min_rating);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Handle special sort options
        if ($sortBy === 'rating') {
            $query->orderBy('rating', 'desc');
        } elseif ($sortBy === 'price_low') {
            $query->orderBy('hourly_rate', 'asc');
        } elseif ($sortBy === 'price_high') {
            $query->orderBy('hourly_rate', 'desc');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 12);
        $listings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'listings' => ListingResource::collection($listings),
                'pagination' => [
                    'total' => $listings->total(),
                    'per_page' => $listings->perPage(),
                    'current_page' => $listings->currentPage(),
                    'last_page' => $listings->lastPage(),
                ]
            ]
        ], 200);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/listings/{id}",
 *         summary="Get listing details",
 *         tags={"Listings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function show($id)
    {
        $listing = ServiceListing::with(['category', 'provider'])->find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        // Increment views count
        $listing->incrementViews();

        return response()->json([
            'success' => true,
            'data' => new ListingResource($listing)
        ], 200);
    }

        /**
 *     @OA\Post(
 *         path="/api/v1/provider/listings",
 *         summary="Create new listing",
 *         tags={"Listings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function store(ListingStoreRequest $request)
    {
        try {
            $listing = ServiceListing::create([
                'provider_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'hourly_rate' => $request->hourly_rate,
                'years_of_experience' => $request->years_of_experience,
                'skills' => $request->skills,
                'languages' => $request->languages,
                'certifications' => $request->certifications,
                'availability' => $request->availability,
                'service_location' => $request->service_location,
                'service_radius' => $request->service_radius,
                'is_available' => $request->is_available ?? true,
                'status' => 'pending', // Admin approval required
            ]);

            $listing->load(['category', 'provider']);

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully. Pending admin approval.',
                'data' => new ListingResource($listing)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Put(
 *         path="/api/v1/provider/listings/{id}",
 *         summary="Update listing",
 *         tags={"Listings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function update(ListingUpdateRequest $request, $id)
    {
        $listing = ServiceListing::where('id', $id)
            ->where('provider_id', $request->user()->id)
            ->first();

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or unauthorized'
            ], 404);
        }

        try {
            $listing->update($request->validated());
            $listing->load(['category', 'provider']);

            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully',
                'data' => new ListingResource($listing)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle listing availability
     */
    public function toggleAvailability(Request $request, $id)
    {
        $listing = ServiceListing::where('id', $id)
            ->where('provider_id', $request->user()->id)
            ->first();

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or unauthorized'
            ], 404);
        }

        try {
            $listing->update([
                'is_available' => !$listing->is_available
            ]);

            return response()->json([
                'success' => true,
                'message' => $listing->is_available ? 'Listing activated' : 'Listing deactivated',
                'data' => [
                    'is_available' => $listing->is_available
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle availability',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Delete(
 *         path="/api/v1/provider/listings/{id}",
 *         summary="Delete listing",
 *         tags={"Listings"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function destroy(Request $request, $id)
    {
        $listing = ServiceListing::where('id', $id)
            ->where('provider_id', $request->user()->id)
            ->first();

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found or unauthorized'
            ], 404);
        }

        try {
            $listing->delete();

            return response()->json([
                'success' => true,
                'message' => 'Listing deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/listings/featured",
 *         summary="Get featured listings",
 *         tags={"Listings"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function featured()
    {
        $listings = ServiceListing::with(['category', 'provider'])
            ->active()
            ->featured()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ListingResource::collection($listings)
        ], 200);
    }
}
