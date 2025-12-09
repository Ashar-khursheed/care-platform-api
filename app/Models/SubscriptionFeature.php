<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_id',
        'name',
        'description',
        'is_included',
        'sort_order',
    ];

    protected $casts = [
        'is_included' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Scopes
     */
    public function scopeIncluded($query)
    {
        return $query->where('is_included', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}