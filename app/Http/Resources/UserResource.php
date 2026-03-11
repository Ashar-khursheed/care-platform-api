<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'profile_photo' => $this->profile_photo ? url('storage/' . $this->profile_photo) : null,
            'bio' => $this->bio,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'is_verified' => (bool) $this->is_verified,
            'business_name' => $this->business_name,
            'facility_type' => $this->facility_type,
            'desired_role' => $this->desired_role,
            'profile_completion_percentage' => (int) $this->profile_completion_percentage,
            'intro_video' => $this->intro_video_path ? \Illuminate\Support\Facades\Storage::disk('s3')->url($this->intro_video_path) : null,
            'availability_settings' => $this->availability_settings,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'phone_verified_at' => $this->phone_verified_at?->format('Y-m-d H:i:s'),
            'last_active_at' => $this->last_active_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}