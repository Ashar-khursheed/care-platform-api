<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBar;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AdminAnnouncementController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/cms/announcements',
        summary: 'Get all announcements',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index()
    {
        $announcements = AnnouncementBar::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $announcements,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/announcements/{id}',
        summary: 'Get announcement details',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show($id)
{
    $announcement = AnnouncementBar::find($id);

    if (!$announcement) {
        return response()->json([
            'success' => false,
            'message' => 'Announcement not found',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $announcement,
    ]);
}


    #[OA\Post(
        path: '/api/v1/admin/cms/announcements',
        summary: 'Create announcement',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'link_text' => 'nullable|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'background_color' => 'nullable|string',
            'text_color' => 'nullable|string',
            'icon' => 'nullable|string',
            'is_dismissible' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $announcement = AnnouncementBar::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Announcement created successfully',
            'data' => $announcement,
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/announcements/{id}',
        summary: 'Update announcement',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'The id of the resource', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function update(Request $request, $id)
    {
        $announcement = AnnouncementBar::findOrFail($id);

        $request->validate([
            'message' => 'nullable|string',
            'link_text' => 'nullable|string|max:255',
            'link_url' => 'nullable|string|max:255',
            'background_color' => 'nullable|string',
            'text_color' => 'nullable|string',
            'icon' => 'nullable|string',
            'is_dismissible' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $announcement->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Announcement updated successfully',
            'data' => $announcement->fresh(),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/cms/announcements/{id}',
        summary: 'Delete announcement',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'The id of the resource', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function destroy($id)
    {
        $announcement = AnnouncementBar::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/announcements/current',
        summary: 'Get current announcement',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function getCurrent()
    {
        $announcement = AnnouncementBar::getCurrent();

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ]);
    }
}

