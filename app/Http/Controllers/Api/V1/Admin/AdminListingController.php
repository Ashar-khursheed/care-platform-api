<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\ServiceListing;
use Illuminate\Http\Request;

class AdminListingController extends Controller
{
        /**
 *     @OA\Get(
 *         path="/api/v1/admin/listings",
 *         summary="Get all listings",
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
    public function index(Request $request)
    {
        $query = ServiceListing::with(['category', 'provider']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
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
 *         path="/api/v1/admin/listings/{id}",
 *         summary="Get listing details",
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
    public function show($id)
    {
        $listing = ServiceListing::with(['category', 'provider'])->find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ListingResource($listing)
        ], 200);
    }


    /**
     * Get pending listings
     */
    public function getPendingListings(Request $request)
    {
        $listings = ServiceListing::with(['category', 'provider'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

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
 *     @OA\Put(
 *         path="/api/v1/admin/listings/{id}/approve",
 *         summary="Approve listing",
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
    public function approve($id)
    {
        $listing = ServiceListing::find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        try {
            $listing->update(['status' => 'active']);
            $listing->load(['category', 'provider']);

            // TODO: Send notification to provider

            return response()->json([
                'success' => true,
                'message' => 'Listing approved successfully',
                'data' => new ListingResource($listing)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Put(
 *         path="/api/v1/admin/listings/{id}/reject",
 *         summary="Reject listing",
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
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $listing = ServiceListing::find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        try {
            $listing->update(['status' => 'rejected']);
            
            // TODO: Send notification to provider with rejection reason

            return response()->json([
                'success' => true,
                'message' => 'Listing rejected',
                'data' => [
                    'rejection_reason' => $request->reason
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Suspend listing
     */
    public function suspend($id)
    {
        $listing = ServiceListing::find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        try {
            $listing->update(['status' => 'suspended']);

            return response()->json([
                'success' => true,
                'message' => 'Listing suspended successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to suspend listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
 *     @OA\Delete(
 *         path="/api/v1/admin/listings/{id}",
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
    public function destroy($id)
    {
        $listing = ServiceListing::find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        try {
            $listing->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Listing deleted permanently'
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
 *     @OA\Put(
 *         path="/api/v1/admin/listings/{id}/feature",
 *         summary="Feature listing",
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
    public function feature(Request $request, $id)
    {
        $request->validate([
            'featured_days' => 'required|integer|min:1|max:365'
        ]);

        $listing = ServiceListing::find($id);

        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found'
            ], 404);
        }

        try {
            $listing->update([
                'is_featured' => true,
                'featured_until' => now()->addDays($request->featured_days)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Listing featured for {$request->featured_days} days",
                'data' => [
                    'is_featured' => true,
                    'featured_until' => $listing->featured_until->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to feature listing',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
