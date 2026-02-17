<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MessageResource",
    title: "Message Resource",
    description: "Message details",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "conversation_id", type: "integer", example: 1),
        new OA\Property(property: "sender", type: "object", properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "name", type: "string", example: "Jane Doe"),
            new OA\Property(property: "profile_photo", type: "string", nullable: true, example: "https://example.com/photo.jpg"),
        ]),
        new OA\Property(property: "receiver", type: "object", properties: [
            new OA\Property(property: "id", type: "integer", example: 2),
            new OA\Property(property: "name", type: "string", example: "John Doe"),
            new OA\Property(property: "profile_photo", type: "string", nullable: true, example: "https://example.com/photo.jpg"),
        ]),
        new OA\Property(property: "message", type: "string", nullable: true, example: "Hello!"),
        new OA\Property(property: "has_attachment", type: "boolean", example: false),
        new OA\Property(property: "attachment", type: "object", nullable: true, properties: [
            new OA\Property(property: "type", type: "string", example: "image"),
            new OA\Property(property: "name", type: "string", example: "photo.jpg"),
            new OA\Property(property: "size", type: "integer", example: 1024),
            new OA\Property(property: "url", type: "string", example: "https://example.com/storage/messages/1/images/photo.jpg"),
        ]),
        new OA\Property(property: "status", type: "string", example: "read"),
        new OA\Property(property: "is_read", type: "boolean", example: true),
        new OA\Property(property: "delivered_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "read_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "is_edited", type: "boolean", example: false),
        new OA\Property(property: "sent_by_me", type: "boolean", example: true),
        new OA\Property(property: "is_flagged", type: "boolean", example: false),
        new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-10-15 14:30:00"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-10-15 14:30:00"),
    ]
)]
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()->id;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            
            // Sender Info
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->first_name . ' ' . $this->sender->last_name,
                'profile_photo' => $this->sender->profile_photo 
                    ? url('storage/' . $this->sender->profile_photo) 
                    : null,
            ],
            
            // Receiver Info
            'receiver' => [
                'id' => $this->receiver->id,
                'name' => $this->receiver->first_name . ' ' . $this->receiver->last_name,
                'profile_photo' => $this->receiver->profile_photo 
                    ? url('storage/' . $this->receiver->profile_photo) 
                    : null,
            ],
            
            // Message Content
            'message' => $this->message,
            
            // Attachment
            'has_attachment' => $this->hasAttachment(),
            'attachment' => $this->when($this->hasAttachment(), [
                'type' => $this->attachment_type,
                'name' => $this->attachment_name,
                'size' => $this->attachment_size,
                'url' => $this->getAttachmentUrl(),
            ]),
            
            // Message Status
            'status' => $this->status,
            'is_read' => $this->isRead(),
            'delivered_at' => $this->delivered_at 
                ? $this->delivered_at->format('Y-m-d H:i:s') 
                : null,
            'read_at' => $this->read_at 
                ? $this->read_at->format('Y-m-d H:i:s') 
                : null,
            
            // Flags
            'is_edited' => $this->is_edited,
            'sent_by_me' => $this->sender_id === $currentUserId,
            
            // Moderation (only for admin)
            'is_flagged' => $this->when(
                $request->user() && $request->user()->isAdmin(),
                $this->is_flagged
            ),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}