<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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