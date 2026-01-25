<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\AnnouncementBar;
use App\Models\Page;
use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AdminSeoController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/cms/seo',
        summary: 'Get all SEO settings',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index()
    {
        $settings = SeoSetting::all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/seo/{pageType}',
        summary: 'Get SEO by page type',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'pageType', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function show($pageType)
    {
        $setting = SeoSetting::getByPageType($pageType);

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/seo/{pageType}',
        summary: 'Update SEO settings',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'pageType', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    public function update(Request $request, $pageType)
    {
        $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string',
            'og_type' => 'nullable|string',
            'twitter_card' => 'nullable|string',
            'schema_markup' => 'nullable|string',
            'custom_head_scripts' => 'nullable|string',
            'custom_body_scripts' => 'nullable|string',
        ]);

        $setting = SeoSetting::setForPageType($pageType, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'SEO settings updated successfully',
            'data' => $setting,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cms/seo/clear-cache',
        summary: 'Clear SEO cache',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function clearCache()
    {
        SeoSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'SEO cache cleared successfully',
        ]);
    }
}
