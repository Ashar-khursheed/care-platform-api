<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'category_id',
        'title',
        'description',
        'hourly_rate',
        'years_of_experience',
        'skills',
        'languages',
        'certifications',
        'availability',
        'service_location',
        'service_radius',
        'is_available',
        'is_featured',
        'status',
        'views_count',
        'rating',
        'reviews_count',
        'featured_until',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'service_radius' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'skills' => 'array',
        'languages' => 'array',
        'certifications' => 'array',
        'availability' => 'array',
        'featured_until' => 'datetime',
    ];

    /**
     * Get the provider who owns the listing
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /**
     * Check if listing is featured
     */
    public function isFeatured(): bool
    {
        return $this->is_featured && 
               $this->featured_until && 
               $this->featured_until->isFuture();
    }

    /**
     * Check if listing is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_available;
    }

    /**
     * Increment views count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Scope: Active listings
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('is_available', true);
    }

    /**
     * Scope: Featured listings
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where('featured_until', '>', now());
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope: Search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('service_location', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by price range
     */
    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('hourly_rate', [$min, $max]);
    }

    /**
     * Scope: Filter by rating
     */
    public function scopeMinRating($query, $rating)
    {
        return $query->where('rating', '>=', $rating);
    }
}