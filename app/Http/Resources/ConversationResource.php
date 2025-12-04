<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()->id;
        $otherUser = $this->getOtherUser($currentUserId);

        return [
            'id' => $this->id,
            
            // Other User Info
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->first_name . ' ' . $otherUser->last_name,
                'email' => $otherUser->email,
                'profile_photo' => $otherUser->profile_photo 
                    ? url('storage/' . $otherUser->profile_photo) 
                    : null,
                'user_type' => $otherUser->user_type,
                'is_verified' => $otherUser->is_verified ?? false,
            ],
            
            // Booking Info (if exists)
            'booking' => $this->when($this->booking, [
                'id' => $this->booking->id ?? null,
                'booking_date' => $this->booking->booking_date ?? null,
                'status' => $this->booking->status ?? null,
            ]),
            
            // Last Message
            'last_message' => $this->last_message,
            'last_message_at' => $this->last_message_at 
                ? $this->last_message_at->format('Y-m-d H:i:s') 
                : null,
            'last_message_by_me' => $this->last_message_user_id === $currentUserId,
            
            // Unread Count
            'unread_count' => $this->getUnreadCount($currentUserId),
            
            // Status
            'is_blocked' => $this->is_blocked,
            'blocked_by_me' => $this->blocked_by === $currentUserId,
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}