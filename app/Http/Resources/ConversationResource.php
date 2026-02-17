<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ConversationResource",
    title: "Conversation Resource",
    description: "Conversation details including last message and participants",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "other_user", type: "object", properties: [
            new OA\Property(property: "id", type: "integer", example: 2),
            new OA\Property(property: "name", type: "string", example: "John Doe"),
            new OA\Property(property: "email", type: "string", example: "john@example.com"),
            new OA\Property(property: "profile_photo", type: "string", nullable: true, example: "https://example.com/photo.jpg"),
            new OA\Property(property: "user_type", type: "string", example: "client"),
            new OA\Property(property: "is_verified", type: "boolean", example: true),
        ]),
        new OA\Property(property: "booking", type: "object", nullable: true, properties: [
            new OA\Property(property: "id", type: "integer", example: 10),
            new OA\Property(property: "booking_date", type: "string", format: "date", example: "2023-10-15"),
            new OA\Property(property: "status", type: "string", example: "confirmed"),
        ]),
        new OA\Property(property: "last_message", type: "string", nullable: true, example: "Hello!"),
        new OA\Property(property: "last_message_at", type: "string", format: "date-time", nullable: true, example: "2023-10-15 14:30:00"),
        new OA\Property(property: "last_message_by_me", type: "boolean", example: true),
        new OA\Property(property: "unread_count", type: "integer", example: 0),
        new OA\Property(property: "is_blocked", type: "boolean", example: false),
        new OA\Property(property: "blocked_by_me", type: "boolean", example: false),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-10-01 10:00:00"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-10-15 14:30:00"),
    ]
)]
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