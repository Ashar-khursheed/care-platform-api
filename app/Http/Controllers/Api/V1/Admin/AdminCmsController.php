<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminCmsController extends Controller
{
    /**
     * Get all site settings
     */
  public function getSettings()
{
    $settings = SiteSetting::all()
        ->map(function ($setting) {

            if ($setting->type === 'json' && is_string($setting->value)) {
                $setting->value = json_decode($setting->value, true);
            }

            if ($setting->type === 'boolean') {
                $setting->value = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            }

            return $setting;
        })
        ->groupBy('group');

    return response()->json([
        'success' => true,
        'data' => $settings,
    ]);
}


    /**
     * Get settings by group
     */
    public function getSettingsByGroup($group)
    {
        $settings = SiteSetting::where('group', $group)->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update site settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
            'settings.*.type' => 'nullable|string',
        ]);

        foreach ($request->settings as $setting) {
            $type = $setting['type'] ?? 'text';
            $value = $setting['value'];

            // Handle image uploads
            if ($type === 'image' && $request->hasFile("image_{$setting['key']}")) {
                $file = $request->file("image_{$setting['key']}");
                $path = $file->store('settings', 'public');
                $value = Storage::url($path);
            }

            SiteSetting::set($setting['key'], $value, $type);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Update single setting
     */
    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
            'type' => 'nullable|string',
        ]);

        $type = $request->get('type', 'text');
        $value = $request->value;

        // Handle image upload
        if ($type === 'image' && $request->hasFile('image')) {
            // Delete old image
            $oldSetting = SiteSetting::where('key', $request->key)->first();
            if ($oldSetting && $oldSetting->value) {
                $oldPath = str_replace('/storage/', '', parse_url($oldSetting->value, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('image');
            $path = $file->store('settings', 'public');
            $value = Storage::url($path);
        }

        SiteSetting::set($request->key, $value, $type);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => SiteSetting::where('key', $request->key)->first(),
        ]);
    }

    /**
     * Get header menu
     */
    public function getHeaderMenu()
    {
        $menu = SiteSetting::get('header_menu', []);

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    /**
     * Update header menu
     */
    public function updateHeaderMenu(Request $request)
    {
        $request->validate([
            'menu' => 'required|array',
            'menu.*.label' => 'required|string|max:255',
            'menu.*.url' => 'required|string|max:255',
            'menu.*.order' => 'required|integer',
        ]);

        SiteSetting::set('header_menu', $request->menu, 'json');

        return response()->json([
            'success' => true,
            'message' => 'Header menu updated successfully',
        ]);
    }

    /**
     * Get footer links
     */
    public function getFooterLinks()
    {
        $links = SiteSetting::get('footer_links', []);

        return response()->json([
            'success' => true,
            'data' => $links,
        ]);
    }

    /**
     * Update footer links
     */
    public function updateFooterLinks(Request $request)
    {
        $request->validate([
            'links' => 'required|array',
        ]);

        SiteSetting::set('footer_links', $request->links, 'json');

        return response()->json([
            'success' => true,
            'message' => 'Footer links updated successfully',
        ]);
    }

    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        SiteSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully',
        ]);
    }
}