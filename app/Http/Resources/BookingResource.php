<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'client' => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->full_name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
                'profile_photo' => $this->client->profile_photo ? url('storage/' . $this->client->profile_photo) : null,
            ] : null,
        
            'provider' => $this->provider ? [
                'id' => $this->provider->id,
                'name' => $this->provider->full_name,
                'email' => $this->provider->email,
                'phone' => $this->provider->phone,
                'profile_photo' => $this->provider->profile_photo ? url('storage/' . $this->provider->profile_photo) : null,
                'is_verified' => $this->provider->is_verified,
            ] : null,
        
            'listing' => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
                'category' => $this->listing->category?->name,
            ] : null,
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'hours' => (float) $this->hours,
            'hourly_rate' => (float) $this->hourly_rate,
            'total_amount' => (float) $this->total_amount,
            'service_location' => $this->service_location,
            'special_requirements' => $this->special_requirements,
            'status' => $this->status,
            'payment_status' => $this->latestPayment 
                ? ($this->latestPayment->status === 'succeeded' ? 'paid' : $this->latestPayment->status) 
                : $this->payment_status,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_by' => $this->cancelled_by ? [
                'id' => $this->cancelled_by,
                'name' => $this->cancelledByUser ? $this->cancelledByUser->full_name : null,
            ] : null,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'accepted_at' => $this->accepted_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}