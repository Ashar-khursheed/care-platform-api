<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ServiceCategory;

class CategoryController extends Controller
{
        /**
 *     @OA\Get(
 *         path="/api/v1/categories",
 *         summary="Get all categories",
 *         tags={"Categories"},
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
    public function index()
    {
        $categories = ServiceCategory::active()
            ->ordered()
            ->withCount(['listings' => function($query) {
                $query->where('status', 'active');
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories)
        ], 200);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/categories/{slug}",
 *         summary="Get category by slug",
 *         tags={"Categories"},
 *     @OA\Parameter(
 *         name="slug",
 *         in="path",
 *         required=true,
 *         description="The slug of the resource",
 *         @OA\Schema(type="string")
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
    public function show($slug)
    {
        $category = ServiceCategory::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category)
        ], 200);
    }
}
