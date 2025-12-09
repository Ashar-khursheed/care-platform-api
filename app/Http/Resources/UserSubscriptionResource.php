<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Plan Info
            'plan' => new SubscriptionPlanResource($this->plan),
            
            // Billing
            'billing_cycle' => $this->billing_cycle,
            'amount' => $this->amount,
            'currency' => $this->currency,
            
            // Status
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_trial' => $this->isTrial(),
            'is_canceled' => $this->isCanceled(),
            'is_expired' => $this->isExpired(),
            'auto_renew' => $this->auto_renew,
            
            // Dates
            'trial_ends_at' => $this->trial_ends_at 
                ? $this->trial_ends_at->format('Y-m-d H:i:s') 
                : null,
            'starts_at' => $this->starts_at 
                ? $this->starts_at->format('Y-m-d H:i:s') 
                : null,
            'ends_at' => $this->ends_at 
                ? $this->ends_at->format('Y-m-d H:i:s') 
                : null,
            'canceled_at' => $this->canceled_at 
                ? $this->canceled_at->format('Y-m-d H:i:s') 
                : null,
            'paused_at' => $this->paused_at 
                ? $this->paused_at->format('Y-m-d H:i:s') 
                : null,
            
            // Usage
            'usage' => [
                'listings_used' => $this->listings_used,
                'listings_limit' => $this->plan->max_listings,
                'listings_remaining' => $this->plan->hasUnlimitedListings() 
                    ? 'unlimited' 
                    : max(0, $this->plan->max_listings - $this->listings_used),
                'bookings_used' => $this->bookings_used,
                'bookings_limit' => $this->plan->max_bookings_per_month,
                'bookings_remaining' => $this->plan->hasUnlimitedBookings() 
                    ? 'unlimited' 
                    : max(0, $this->plan->max_bookings_per_month - $this->bookings_used),
                'usage_reset_at' => $this->usage_reset_at 
                    ? $this->usage_reset_at->format('Y-m-d H:i:s') 
                    : null,
            ],
            
            // Helper info
            'days_until_expiry' => $this->daysUntilExpiry(),
            'on_trial' => $this->onTrial(),
            'has_expired' => $this->hasExpired(),
            
            // Cancellation
            'cancellation_reason' => $this->cancellation_reason,
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}