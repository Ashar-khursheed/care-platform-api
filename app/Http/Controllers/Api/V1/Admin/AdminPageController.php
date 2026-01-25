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

class AdminPageController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/cms/pages',
        summary: 'Get all pages',
        security: [['bearerAuth' => []]],
        tags: ['Admin - CMS']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request)
    {
        $query = Page::with('author:id,first_name,last_name');

        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $pages = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cms/pages',
        summary: 'Create page',
        security: [['bearerAuth' => []]],
        tags: ['Admin - CMS']
    )]
    #[OA\Response(response: 201, description: 'Page created')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'template' => 'nullable|in:default,full-width,no-sidebar',
            'is_published' => 'nullable|boolean',
        ]);

        $data = $request->all();
        $data['author_id'] = $request->user()->id;

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->title);
        }

        $page = Page::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/pages/{id}',
        summary: 'Get page details',
        security: [['bearerAuth' => []]],
        tags: ['Admin - CMS']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show($id)
    {
        $page = Page::with('author:id,first_name,last_name')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/pages/{id}',
        summary: 'Update page',
        security: [['bearerAuth' => []]],
        tags: ['Admin - CMS']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Page updated')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $id,
            'content' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        $page->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page->fresh(),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/cms/pages/{id}',
        summary: 'Delete page',
        security: [['bearerAuth' => []]],
        tags: ['Admin - CMS']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Deleted successfully')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }
}
