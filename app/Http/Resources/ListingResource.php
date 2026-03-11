<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => [
                'id' => $this->provider->id,
                'name' => $this->provider->full_name,
                'profile_photo' => $this->provider->profile_photo ? url('storage/' . $this->provider->profile_photo) : null,
                'is_verified' => $this->provider->is_verified,
                'city' => $this->provider->city,
                'state' => $this->provider->state,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
            'title' => $this->title,
            'description' => $this->description,
            'hourly_rate' => (float) $this->hourly_rate,
            'years_of_experience' => $this->years_of_experience,
            'skills' => $this->skills ?? [],
            'languages' => $this->languages ?? [],
            'certifications' => $this->certifications ?? [],
            'availability' => $this->availability ?? [],
            'service_location' => $this->service_location,
            'service_radius' => $this->service_radius ? (float) $this->service_radius : null,
            'is_featured' => $this->isFeatured(),
            'is_urgent' => (bool) $this->is_urgent,
            'quick_pay' => (bool) $this->quick_pay,
            'workers_needed' => (int) $this->workers_needed,
            'shift_date' => $this->shift_date ? $this->shift_date->format('Y-m-d') : null,
            'shift_start_time' => $this->shift_start_time,
            'shift_end_time' => $this->shift_end_time,
            'status' => $this->status,
            'views_count' => $this->views_count,
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'employer_details' => $this->provider->user_type === 'client' ? [
                'business_name' => $this->provider->business_name,
                'facility_type' => $this->provider->facility_type,
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}