<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SeoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'schema_markup',
        'custom_head_scripts',
        'custom_body_scripts',
    ];

    /**
     * Get SEO settings by page type
     */
    public static function getByPageType($pageType)
    {
        return Cache::remember("seo_settings_{$pageType}", 3600, function () use ($pageType) {
            return static::where('page_type', $pageType)->first();
        });
    }

    /**
     * Set SEO settings for page type
     */
    public static function setForPageType($pageType, $data)
    {
        $seo = static::updateOrCreate(
            ['page_type' => $pageType],
            $data
        );

        Cache::forget("seo_settings_{$pageType}");

        return $seo;
    }

    /**
     * Get all SEO settings
     */
    public static function getAllSettings()
    {
        return Cache::remember('all_seo_settings', 3600, function () {
            return static::all()->keyBy('page_type');
        });
    }

    /**
     * Clear SEO cache
     */
    public static function clearCache()
    {
        $pageTypes = static::pluck('page_type');
        foreach ($pageTypes as $pageType) {
            Cache::forget("seo_settings_{$pageType}");
        }
        Cache::forget('all_seo_settings');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($seo) {
            Cache::forget("seo_settings_{$seo->page_type}");
            Cache::forget('all_seo_settings');
        });

        static::deleted(function ($seo) {
            Cache::forget("seo_settings_{$seo->page_type}");
            Cache::forget('all_seo_settings');
        });
    }

    /**
     * Generate meta tags HTML
     */
    public function generateMetaTags($dynamicData = [])
    {
        $tags = [];

        // Basic meta tags
        if ($this->meta_title) {
            $title = $this->replacePlaceholders($this->meta_title, $dynamicData);
            $tags[] = '<title>' . e($title) . '</title>';
            $tags[] = '<meta name="title" content="' . e($title) . '">';
        }

        if ($this->meta_description) {
            $description = $this->replacePlaceholders($this->meta_description, $dynamicData);
            $tags[] = '<meta name="description" content="' . e($description) . '">';
        }

        if ($this->meta_keywords) {
            $tags[] = '<meta name="keywords" content="' . e($this->meta_keywords) . '">';
        }

        // Open Graph tags
        if ($this->og_title) {
            $ogTitle = $this->replacePlaceholders($this->og_title, $dynamicData);
            $tags[] = '<meta property="og:title" content="' . e($ogTitle) . '">';
        }

        if ($this->og_description) {
            $ogDesc = $this->replacePlaceholders($this->og_description, $dynamicData);
            $tags[] = '<meta property="og:description" content="' . e($ogDesc) . '">';
        }

        if ($this->og_image) {
            $tags[] = '<meta property="og:image" content="' . e($this->og_image) . '">';
        }

        if ($this->og_type) {
            $tags[] = '<meta property="og:type" content="' . e($this->og_type) . '">';
        }

        // Twitter Card tags
        if ($this->twitter_card) {
            $tags[] = '<meta name="twitter:card" content="' . e($this->twitter_card) . '">';
        }

        // Schema markup
        if ($this->schema_markup) {
            $schema = $this->replacePlaceholders($this->schema_markup, $dynamicData);
            $tags[] = '<script type="application/ld+json">' . $schema . '</script>';
        }

        return implode("\n", $tags);
    }

    /**
     * Replace placeholders in text
     */
    protected function replacePlaceholders($text, $data)
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }
}