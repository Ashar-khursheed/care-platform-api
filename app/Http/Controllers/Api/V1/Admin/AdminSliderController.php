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

class AdminSliderController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/cms/sliders',
        summary: 'Get all sliders',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function index()
    {
        $sliders = Slider::orderBy('order', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sliders,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cms/sliders',
        summary: 'Create slider',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 201, description: 'Slider created')]
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'required|image|max:5120', // 5MB
            'mobile_image' => 'nullable|image|max:5120',
            'button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|string|max:255',
            'button_style' => 'nullable|in:primary,secondary,outline',
            'order' => 'nullable|integer',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_color' => 'nullable|string',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $data = $request->except(['image', 'mobile_image']);

        // Upload main image
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'slider_' . \Illuminate\Support\Str::random(15) . '_' . time() . '.' . $extension;
            $path = "cms/sliders/desktop/{$filename}";
            
            Storage::disk('s3')->put($path, file_get_contents($file), 'public');
            $data['image'] = Storage::disk('s3')->url($path);
        }

        // Upload mobile image
        if ($request->hasFile('mobile_image')) {
            $file = $request->file('mobile_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'slider_mobile_' . \Illuminate\Support\Str::random(15) . '_' . time() . '.' . $extension;
            $path = "cms/sliders/mobile/{$filename}";
            
            Storage::disk('s3')->put($path, file_get_contents($file), 'public');
            $data['mobile_image'] = Storage::disk('s3')->url($path);
        }

        $slider = Slider::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Slider created successfully',
            'data' => $slider,
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/sliders/{id}',
        summary: 'Get slider details',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function show($id)
    {
        $slider = Slider::find($id);

        if (!$slider) {
            return response()->json([
                'success' => false,
                'message' => 'Slider not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $slider,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/sliders/{id}',
        summary: 'Update slider',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'The id of the resource', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'mobile_image' => 'nullable|image|max:5120',
            'button_text' => 'nullable|string|max:255',
            'button_url' => 'nullable|string|max:255',
            'button_style' => 'nullable|in:primary,secondary,outline',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_color' => 'nullable|string',
            'overlay_opacity' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $data = $request->except(['image', 'mobile_image']);

        // Upload new main image
        if ($request->hasFile('image')) {
            // Delete old image from S3
            if ($slider->image) {
                $oldPath = parse_url($slider->image, PHP_URL_PATH);
                $oldPath = ltrim($oldPath, '/');
                Storage::disk('s3')->delete($oldPath);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'slider_' . \Illuminate\Support\Str::random(15) . '_' . time() . '.' . $extension;
            $path = "cms/sliders/desktop/{$filename}";
            
            Storage::disk('s3')->put($path, file_get_contents($file), 'public');
            $data['image'] = Storage::disk('s3')->url($path);
        }

        // Upload new mobile image
        if ($request->hasFile('mobile_image')) {
            // Delete old mobile image from S3
            if ($slider->mobile_image) {
                $oldPath = parse_url($slider->mobile_image, PHP_URL_PATH);
                $oldPath = ltrim($oldPath, '/');
                Storage::disk('s3')->delete($oldPath);
            }

            $file = $request->file('mobile_image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'slider_mobile_' . \Illuminate\Support\Str::random(15) . '_' . time() . '.' . $extension;
            $path = "cms/sliders/mobile/{$filename}";
            
            Storage::disk('s3')->put($path, file_get_contents($file), 'public');
            $data['mobile_image'] = Storage::disk('s3')->url($path);
        }

        $slider->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Slider updated successfully',
            'data' => $slider->fresh(),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/cms/sliders/{id}',
        summary: 'Delete slider',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, description: 'The id of the resource', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);

        // Delete images from S3
        if ($slider->image) {
            $path = parse_url($slider->image, PHP_URL_PATH);
            $path = ltrim($path, '/');
            Storage::disk('s3')->delete($path);
        }

        if ($slider->mobile_image) {
            $path = parse_url($slider->mobile_image, PHP_URL_PATH);
            $path = ltrim($path, '/');
            Storage::disk('s3')->delete($path);
        }

        $slider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slider deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cms/sliders/reorder',
        summary: 'Reorder sliders',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function reorder(Request $request)
    {
        $request->validate([
            'sliders' => 'required|array',
            'sliders.*.id' => 'required|exists:sliders,id',
            'sliders.*.order' => 'required|integer',
        ]);

        foreach ($request->sliders as $item) {
            Slider::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sliders reordered successfully',
        ]);
    }
}

