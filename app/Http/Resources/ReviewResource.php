<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            
            // Review Details
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            
            // Client Info
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->first_name . ' ' . $this->client->last_name,
                'profile_photo' => $this->client->profile_photo 
                    ? url('storage/' . $this->client->profile_photo) 
                    : null,
            ],
            
            // Provider Info
            'provider' => [
                'id' => $this->provider->id,
                'name' => $this->provider->first_name . ' ' . $this->provider->last_name,
                'profile_photo' => $this->provider->profile_photo 
                    ? url('storage/' . $this->provider->profile_photo) 
                    : null,
                'is_verified' => $this->provider->is_verified,
            ],
            
            // Booking Info
            'booking' => [
                'id' => $this->booking->id,
                'booking_date' => $this->booking->booking_date->format('Y-m-d'),
                'service_type' => $this->listing->title ?? null,
            ],
            
            // Listing Info
            'listing' => $this->when($this->listing, [
                'id' => $this->listing->id ?? null,
                'title' => $this->listing->title ?? null,
                'category' => $this->listing->category->name ?? null,
            ]),
            
            // Provider Response
            'provider_response' => $this->provider_response,
            'response_date' => $this->response_date 
                ? $this->response_date->format('Y-m-d H:i:s') 
                : null,
            
            // Moderation Info (only for admin)
            'moderation' => $this->when($request->user() && $request->user()->isAdmin(), [
                'is_flagged' => $this->is_flagged,
                'flag_reason' => $this->flag_reason,
                'rejection_reason' => $this->rejection_reason,
                'moderated_by' => $this->moderator ? [
                    'id' => $this->moderator->id,
                    'name' => $this->moderator->first_name . ' ' . $this->moderator->last_name,
                ] : null,
                'moderated_at' => $this->moderated_at 
                    ? $this->moderated_at->format('Y-m-d H:i:s') 
                    : null,
            ]),
            
            // Engagement
            'helpful_count' => $this->helpful_count,
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Edit permissions (only for owner)
            'can_edit' => $this->when(
                $request->user() && $request->user()->id === $this->client_id,
                $this->canBeEdited()
            ),
            'can_delete' => $this->when(
                $request->user() && $request->user()->id === $this->client_id,
                $this->canBeDeleted()
            ),
        ];
    }
}