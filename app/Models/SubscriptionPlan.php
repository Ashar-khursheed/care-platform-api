<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'yearly_price',
        'currency',
        'stripe_plan_id',
        'stripe_yearly_plan_id',
        'max_listings',
        'max_bookings_per_month',
        'featured_listings_allowed',
        'max_featured_listings',
        'priority_support',
        'analytics_access',
        'api_access',
        'trial_days',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'max_listings' => 'integer',
        'max_bookings_per_month' => 'integer',
        'max_featured_listings' => 'integer',
        'featured_listings_allowed' => 'boolean',
        'priority_support' => 'boolean',
        'analytics_access' => 'boolean',
        'api_access' => 'boolean',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function features()
    {
        return $this->hasMany(SubscriptionFeature::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Helper Methods
     */
    public function isFree()
    {
        return $this->price == 0;
    }

    public function hasTrial()
    {
        return $this->trial_days > 0;
    }

    public function hasUnlimitedListings()
    {
        return $this->max_listings == 0;
    }

    public function hasUnlimitedBookings()
    {
        return $this->max_bookings_per_month == 0;
    }

    /**
     * Calculate yearly savings
     */
    public function getYearlySavings()
    {
        if (!$this->yearly_price) {
            return 0;
        }
        
        $monthlyTotal = $this->price * 12;
        return $monthlyTotal - $this->yearly_price;
    }

    /**
     * Get savings percentage
     */
    public function getSavingsPercentage()
    {
        if (!$this->yearly_price || $this->price == 0) {
            return 0;
        }
        
        $monthlyTotal = $this->price * 12;
        return round((($monthlyTotal - $this->yearly_price) / $monthlyTotal) * 100);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPrice()
    {
        if ($this->isFree()) {
            return 'Free';
        }
        
        return '$' . number_format($this->price, 2) . '/' . strtolower($this->currency);
    }
}