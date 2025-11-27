<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\ServiceCategory;

class CategoryController extends Controller
{
    /**
     * Get all active categories
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
     * Get specific category with listings
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