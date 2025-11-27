<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get listings for this category
     */
    public function listings()
    {
        return $this->hasMany(ServiceListing::class, 'category_id');
    }

    /**
     * Get active listings count
     */
    public function getActiveListingsCountAttribute()
    {
        return $this->listings()->where('status', 'active')->count();
    }

    /**
     * Scope: Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}