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
            
            // Client Info - WITH NULL CHECKS
            'client' => $this->when($this->client, [
                'id' => $this->client->id ?? null,
                'name' => ($this->client->first_name ?? '') . ' ' . ($this->client->last_name ?? ''),
                'profile_photo' => $this->client && $this->client->profile_photo 
                    ? url('storage/' . $this->client->profile_photo) 
                    : null,
            ]),
            
            // Provider Info - WITH NULL CHECKS
            'provider' => $this->when($this->provider, [
                'id' => $this->provider->id ?? null,
                'name' => ($this->provider->first_name ?? '') . ' ' . ($this->provider->last_name ?? ''),
                'profile_photo' => $this->provider && $this->provider->profile_photo 
                    ? url('storage/' . $this->provider->profile_photo) 
                    : null,
                'is_verified' => $this->provider->is_verified ?? false,
            ]),
            
            // Booking Info - WITH NULL CHECKS
            'booking' => $this->when($this->booking, [
                'id' => $this->booking->id ?? null,
                'booking_date' => $this->booking && $this->booking->booking_date 
                    ? $this->booking->booking_date->format('Y-m-d') 
                    : null,
                'service_type' => $this->listing->title ?? null,
            ]),
            
            // Listing Info - WITH NULL CHECKS
            'listing' => $this->when($this->listing, [
                'id' => $this->listing->id ?? null,
                'title' => $this->listing->title ?? null,
                'category' => $this->listing && $this->listing->category 
                    ? $this->listing->category->name 
                    : null,
            ]),
            
            // Provider Response
            'provider_response' => $this->provider_response,
            'response_date' => $this->response_date 
                ? $this->response_date->format('Y-m-d H:i:s') 
                : null,
            
            // Moderation Info (only for admin) - FIXED
            'moderation' => $this->when(
                $request->user() && method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin(),
                function() {
                    return [
                        'is_flagged' => $this->is_flagged ?? false,
                        'flag_reason' => $this->flag_reason,
                        'rejection_reason' => $this->rejection_reason,
                        'moderated_by' => $this->moderator ? [
                            'id' => $this->moderator->id,
                            'name' => ($this->moderator->first_name ?? '') . ' ' . ($this->moderator->last_name ?? ''),
                        ] : null,
                        'moderated_at' => $this->moderated_at 
                            ? $this->moderated_at->format('Y-m-d H:i:s') 
                            : null,
                    ];
                }
            ),
            
            // Engagement
            'helpful_count' => $this->helpful_count ?? 0,
            
            // Timestamps
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            
            // Edit permissions (only for owner) - FIXED
            'can_edit' => $this->when(
                $request->user() && $request->user()->id === $this->client_id,
                function() {
                    return method_exists($this, 'canBeEdited') ? $this->canBeEdited() : false;
                }
            ),
            'can_delete' => $this->when(
                $request->user() && $request->user()->id === $this->client_id,
                function() {
                    return method_exists($this, 'canBeDeleted') ? $this->canBeDeleted() : false;
                }
            ),
        ];
    }
}