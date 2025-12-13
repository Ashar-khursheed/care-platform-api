<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\AnnouncementBar;
use App\Models\Page;
use App\Models\SeoSetting;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    /**
     * Get all active sliders
     */
    public function getSliders()
    {
        $sliders = Slider::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $sliders,
        ]);
    }

    /**
     * Get current announcement bar
     */
    public function getAnnouncement()
    {
        $announcement = AnnouncementBar::getCurrent();

        return response()->json([
            'success' => true,
            'data' => $announcement,
        ]);
    }

    /**
     * Get site settings
     */
    public function getSettings()
    {
        $settings = [
            'general' => SiteSetting::getByGroup('general'),
            'header' => SiteSetting::getByGroup('header'),
            'footer' => SiteSetting::getByGroup('footer'),
            'social' => SiteSetting::getByGroup('social'),
            'contact' => SiteSetting::getByGroup('contact'),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get header configuration
     */
    public function getHeader()
    {
        $header = [
            'logo' => SiteSetting::get('site_logo'),
            'site_name' => SiteSetting::get('site_name'),
            'menu' => SiteSetting::get('header_menu', []),
            'style' => SiteSetting::get('header_style', 'default'),
            'show_announcement' => SiteSetting::get('show_announcement_bar', true),
        ];

        // Add announcement if enabled
        if ($header['show_announcement']) {
            $header['announcement'] = AnnouncementBar::getCurrent();
        }

        return response()->json([
            'success' => true,
            'data' => $header,
        ]);
    }

    /**
     * Get footer configuration
     */
    public function getFooter()
    {
        $footer = [
            'logo' => SiteSetting::get('site_logo'),
            'about' => SiteSetting::get('footer_about'),
            'copyright' => SiteSetting::get('footer_copyright'),
            'links' => SiteSetting::get('footer_links', []),
            'social' => [
                'facebook' => SiteSetting::get('social_facebook'),
                'twitter' => SiteSetting::get('social_twitter'),
                'instagram' => SiteSetting::get('social_instagram'),
                'linkedin' => SiteSetting::get('social_linkedin'),
            ],
            'contact' => [
                'email' => SiteSetting::get('contact_email'),
                'phone' => SiteSetting::get('contact_phone'),
                'address' => SiteSetting::get('contact_address'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $footer,
        ]);
    }

    /**
     * Get page by slug
     */
    public function getPage($slug)
    {
        try {
            $page = Page::findBySlug($slug);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content' => $page->content,
                    'excerpt' => $page->excerpt,
                    'featured_image' => $page->featured_image,
                    'template' => $page->template,
                    'published_at' => $page->published_at,
                    'reading_time' => $page->getReadingTime(),
                    'author' => [
                        'name' => $page->author->first_name . ' ' . $page->author->last_name,
                    ],
                    'seo' => [
                        'meta_title' => $page->meta_title,
                        'meta_description' => $page->meta_description,
                        'meta_keywords' => $page->meta_keywords,
                        'og_image' => $page->og_image,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found',
            ], 404);
        }
    }

    /**
     * Get all published pages
     */
    public function getPages()
    {
        $pages = Page::published()
            ->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at')
            ->latest('published_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get menu pages
     */
    public function getMenuPages()
    {
        $pages = Page::published()
            ->inMenu()
            ->select('id', 'title', 'slug', 'menu_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    /**
     * Get SEO settings for page type
     */
    public function getSeo($pageType)
    {
        $seo = SeoSetting::getByPageType($pageType);

        if (!$seo) {
            return response()->json([
                'success' => false,
                'message' => 'SEO settings not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $seo,
        ]);
    }

    /**
     * Get complete frontend configuration
     */
    public function getFrontendConfig()
    {
        $config = [
            'site' => [
                'name' => SiteSetting::get('site_name'),
                'tagline' => SiteSetting::get('site_tagline'),
                'logo' => SiteSetting::get('site_logo'),
                'favicon' => SiteSetting::get('site_favicon'),
            ],
            'header' => [
                'menu' => SiteSetting::get('header_menu', []),
                'style' => SiteSetting::get('header_style', 'default'),
            ],
            'footer' => [
                'about' => SiteSetting::get('footer_about'),
                'copyright' => SiteSetting::get('footer_copyright'),
                'links' => SiteSetting::get('footer_links', []),
            ],
            'social' => [
                'facebook' => SiteSetting::get('social_facebook'),
                'twitter' => SiteSetting::get('social_twitter'),
                'instagram' => SiteSetting::get('social_instagram'),
                'linkedin' => SiteSetting::get('social_linkedin'),
            ],
            'contact' => [
                'email' => SiteSetting::get('contact_email'),
                'phone' => SiteSetting::get('contact_phone'),
                'address' => SiteSetting::get('contact_address'),
            ],
            'announcement' => null,
        ];

        // Add announcement if enabled
        if (SiteSetting::get('show_announcement_bar', true)) {
            $config['announcement'] = AnnouncementBar::getCurrent();
        }

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }
}