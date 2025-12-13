<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'template',
        'is_published',
        'show_in_menu',
        'menu_order',
        'author_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_menu' => 'boolean',
        'menu_order' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            // Ensure unique slug
            $count = 1;
            $originalSlug = $page->slug;
            while (static::where('slug', $page->slug)->exists()) {
                $page->slug = $originalSlug . '-' . $count;
                $count++;
            }

            // Set published_at if publishing
            if ($page->is_published && !$page->published_at) {
                $page->published_at = now();
            }
        });

        static::updating(function ($page) {
            // Set published_at when first published
            if ($page->isDirty('is_published') && $page->is_published && !$page->published_at) {
                $page->published_at = now();
            }
        });
    }

    /**
     * Relationship: Author
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for menu pages
     */
    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true)
                    ->orderBy('menu_order', 'asc');
    }

    /**
     * Get page by slug
     */
    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)->published()->firstOrFail();
    }

    /**
     * Get meta title (fallback to title)
     */
    public function getMetaTitleAttribute($value)
    {
        return $value ?? $this->title;
    }

    /**
     * Get excerpt (auto-generate if empty)
     */
    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Auto-generate excerpt from content
        return Str::limit(strip_tags($this->content), 160);
    }

    /**
     * Get reading time in minutes
     */
    public function getReadingTime()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // Average reading speed: 200 words/min
        return $minutes;
    }
}