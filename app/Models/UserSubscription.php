<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'paused_at',
        'listings_used',
        'bookings_used',
        'usage_reset_at',
        'auto_renew',
        'cancellation_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'paused_at' => 'datetime',
        'usage_reset_at' => 'datetime',
        'listings_used' => 'integer',
        'bookings_used' => 'integer',
        'auto_renew' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Helper Methods
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isTrial()
    {
        return $this->status === 'trial';
    }

    public function isCanceled()
    {
        return $this->status === 'canceled';
    }

    public function isExpired()
    {
        return $this->status === 'expired';
    }

    public function isPaused()
    {
        return $this->status === 'paused';
    }

    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasExpired()
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function daysUntilExpiry()
    {
        if (!$this->ends_at) {
            return null;
        }
        
        return now()->diffInDays($this->ends_at, false);
    }

    /**
     * Check if can create listing
     */
    public function canCreateListing()
    {
        // Unlimited listings
        if ($this->plan->hasUnlimitedListings()) {
            return true;
        }
        
        // Check usage
        return $this->listings_used < $this->plan->max_listings;
    }

    /**
     * Check if can create booking
     */
    public function canCreateBooking()
    {
        // Reset usage if needed
        $this->resetUsageIfNeeded();
        
        // Unlimited bookings
        if ($this->plan->hasUnlimitedBookings()) {
            return true;
        }
        
        // Check usage
        return $this->bookings_used < $this->plan->max_bookings_per_month;
    }

    /**
     * Increment listing usage
     */
    public function incrementListingUsage()
    {
        $this->increment('listings_used');
    }

    /**
     * Increment booking usage
     */
    public function incrementBookingUsage()
    {
        $this->resetUsageIfNeeded();
        $this->increment('bookings_used');
    }

    /**
     * Reset usage if month has changed
     */
    public function resetUsageIfNeeded()
    {
        if (!$this->usage_reset_at || $this->usage_reset_at->isPast()) {
            $this->update([
                'bookings_used' => 0,
                'usage_reset_at' => now()->addMonth(),
            ]);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);

        // Update user status
        $this->user->update([
            'subscription_status' => 'canceled',
        ]);
    }

    /**
     * Renew subscription
     */
    public function renew()
    {
        $this->update([
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => $this->billing_cycle === 'yearly' 
                ? now()->addYear() 
                : now()->addMonth(),
        ]);

        // Update user status
        $this->user->update([
            'subscription_status' => 'active',
        ]);
    }

    /**
     * Pause subscription
     */
    public function pause()
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        // Update user status
        $this->user->update([
            'subscription_status' => 'paused',
        ]);
    }

    /**
     * Resume subscription
     */
    public function resume()
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
        ]);

        // Update user status
        $this->user->update([
            'subscription_status' => 'active',
        ]);
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::created(function ($subscription) {
            // Update user's current subscription
            $subscription->user->update([
                'current_subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
            ]);
        });

        static::updated(function ($subscription) {
            // Sync status with user
            if ($subscription->user->current_subscription_id === $subscription->id) {
                $subscription->user->update([
                    'subscription_status' => $subscription->status,
                ]);
            }
        });
    }
}