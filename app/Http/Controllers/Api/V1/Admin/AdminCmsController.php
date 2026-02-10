<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class AdminCmsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/cms/settings',
        summary: 'Get all site settings',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
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

    #[OA\Get(
        path: '/api/v1/admin/cms/settings/{group}',
        summary: 'Get settings by group',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'group', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function getSettingsByGroup($group)
    {
        $settings = SiteSetting::where('group', $group)->get();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/cms/settings',
        summary: 'Update CMS settings',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 400, description: 'Bad Request')]
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

            if ($type === 'image' && $request->hasFile("image_{$setting['key']}")) {
                $file = $request->file("image_{$setting['key']}");
                $extension = $file->getClientOriginalExtension();
                $filename = $setting['key'] . '_' . time() . '.' . $extension;
                $path = "cms/settings/{$setting['key']}/{$filename}";
                
                Storage::disk('s3')->put($path, file_get_contents($file));
                $value = Storage::disk('s3')->url($path);
            }

            SiteSetting::set($setting['key'], $value, $type);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/settings/single',
        summary: 'Update single setting',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
            'type' => 'nullable|string',
        ]);

        $type = $request->get('type', 'text');
        $value = $request->value;

        if ($type === 'image' && $request->hasFile('image')) {
            $oldSetting = SiteSetting::where('key', $request->key)->first();
            if ($oldSetting && $oldSetting->value) {
                $oldPath = parse_url($oldSetting->value, PHP_URL_PATH);
                $oldPath = ltrim($oldPath, '/');
                Storage::disk('s3')->delete($oldPath);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = $request->key . '_' . time() . '.' . $extension;
            $path = "cms/settings/{$request->key}/{$filename}";
            
            Storage::disk('s3')->put($path, file_get_contents($file));
            $value = Storage::disk('s3')->url($path);
        }

        SiteSetting::set($request->key, $value, $type);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => SiteSetting::where('key', $request->key)->first(),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/cms/header/menu',
        summary: 'Get header menu',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function getHeaderMenu()
    {
        $menu = SiteSetting::get('header_menu', []);

        return response()->json([
            'success' => true,
            'data' => $menu,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/header/menu',
        summary: 'Update header menu',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
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

    #[OA\Get(
        path: '/api/v1/admin/cms/footer/links',
        summary: 'Get footer links',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function getFooterLinks()
    {
        $links = SiteSetting::get('footer_links', []);

        return response()->json([
            'success' => true,
            'data' => $links,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/cms/footer/links',
        summary: 'Update footer links',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
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

    #[OA\Post(
        path: '/api/v1/admin/cms/settings/clear-cache',
        summary: 'Clear CMS cache',
        tags: ['Admin - CMS'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function clearCache()
    {
        SiteSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Settings cache cleared successfully',
        ]);
    }
}
