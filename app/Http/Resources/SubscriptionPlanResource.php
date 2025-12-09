<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Pricing
            'price' => $this->price,
            'yearly_price' => $this->yearly_price,
            'currency' => $this->currency,
            'formatted_price' => $this->getFormattedPrice(),
            'yearly_savings' => $this->getYearlySavings(),
            'savings_percentage' => $this->getSavingsPercentage(),
            
            // Limits
            'limits' => [
                'max_listings' => $this->max_listings,
                'max_bookings_per_month' => $this->max_bookings_per_month,
                'max_featured_listings' => $this->max_featured_listings,
                'unlimited_listings' => $this->hasUnlimitedListings(),
                'unlimited_bookings' => $this->hasUnlimitedBookings(),
            ],
            
            // Features
            'features' => [
                'featured_listings_allowed' => $this->featured_listings_allowed,
                'priority_support' => $this->priority_support,
                'analytics_access' => $this->analytics_access,
                'api_access' => $this->api_access,
            ],
            
            // Additional features list
            'feature_list' => $this->when(
                $this->relationLoaded('features'),
                SubscriptionFeatureResource::collection($this->features)
            ),
            
            // Trial
            'trial_days' => $this->trial_days,
            'has_trial' => $this->hasTrial(),
            
            // Status
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'is_free' => $this->isFree(),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}