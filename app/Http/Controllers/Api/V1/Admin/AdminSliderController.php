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

/**
 * Admin Slider Controller
 */
class AdminSliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::orderBy('order', 'asc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $sliders,
        ]);
    }

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
            $path = $request->file('image')->store('sliders', 'public');
            $data['image'] = Storage::url($path);
        }

        // Upload mobile image
        if ($request->hasFile('mobile_image')) {
            $path = $request->file('mobile_image')->store('sliders/mobile', 'public');
            $data['mobile_image'] = Storage::url($path);
        }

        $slider = Slider::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Slider created successfully',
            'data' => $slider,
        ], 201);
    }

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
            // Delete old image
            if ($slider->image) {
                $oldPath = str_replace('/storage/', '', parse_url($slider->image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('sliders', 'public');
            $data['image'] = Storage::url($path);
        }

        // Upload new mobile image
        if ($request->hasFile('mobile_image')) {
            // Delete old mobile image
            if ($slider->mobile_image) {
                $oldPath = str_replace('/storage/', '', parse_url($slider->mobile_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('mobile_image')->store('sliders/mobile', 'public');
            $data['mobile_image'] = Storage::url($path);
        }

        $slider->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Slider updated successfully',
            'data' => $slider->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);

        // Delete images
        if ($slider->image) {
            $path = str_replace('/storage/', '', parse_url($slider->image, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        if ($slider->mobile_image) {
            $path = str_replace('/storage/', '', parse_url($slider->mobile_image, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $slider->delete();

        return response()->json([
            'success' => true,
            'message' => 'Slider deleted successfully',
        ]);
    }

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

/**
 * Admin Announcement Controller
 */
class AdminAnnouncementController extends Controller
{
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

    public function destroy($id)
    {
        $announcement = AnnouncementBar::findOrFail($id);
        $announcement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Announcement deleted successfully',
        ]);
    }

    public function getCurrent()
    {
        $announcement = AnnouncementBar::getCurrent();

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ]);
    }
}

/**
 * Admin Page Controller
 */
class AdminPageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::with('author:id,first_name,last_name');

        // Filter by published status
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        // Search
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

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120',
            'template' => 'nullable|in:default,full-width,no-sidebar',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
            'menu_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['featured_image', 'og_image']);
        $data['author_id'] = $request->user()->id;

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($request->title);
        }

        // Upload featured image
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('pages', 'public');
            $data['featured_image'] = Storage::url($path);
        }

        // Upload OG image
        if ($request->hasFile('og_image')) {
            $path = $request->file('og_image')->store('pages/og', 'public');
            $data['og_image'] = Storage::url($path);
        }

        $page = Page::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data' => $page,
        ], 201);
    }

    public function show($id)
    {
        $page = Page::with('author:id,first_name,last_name')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $id,
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120',
            'template' => 'nullable|in:default,full-width,no-sidebar',
            'is_published' => 'nullable|boolean',
            'show_in_menu' => 'nullable|boolean',
            'menu_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'og_image' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['featured_image', 'og_image']);

        // Upload new featured image
        if ($request->hasFile('featured_image')) {
            if ($page->featured_image) {
                $oldPath = str_replace('/storage/', '', parse_url($page->featured_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('featured_image')->store('pages', 'public');
            $data['featured_image'] = Storage::url($path);
        }

        // Upload new OG image
        if ($request->hasFile('og_image')) {
            if ($page->og_image) {
                $oldPath = str_replace('/storage/', '', parse_url($page->og_image, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('og_image')->store('pages/og', 'public');
            $data['og_image'] = Storage::url($path);
        }

        $page->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully',
            'data' => $page->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);

        // Delete images
        if ($page->featured_image) {
            $path = str_replace('/storage/', '', parse_url($page->featured_image, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        if ($page->og_image) {
            $path = str_replace('/storage/', '', parse_url($page->og_image, PHP_URL_PATH));
            Storage::disk('public')->delete($path);
        }

        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully',
        ]);
    }
}

/**
 * Admin SEO Controller
 */
class AdminSeoController extends Controller
{
    public function index()
    {
        $settings = SeoSetting::all();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    public function show($pageType)
    {
        $setting = SeoSetting::getByPageType($pageType);

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }

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

    public function clearCache()
    {
        SeoSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'SEO cache cleared successfully',
        ]);
    }
}