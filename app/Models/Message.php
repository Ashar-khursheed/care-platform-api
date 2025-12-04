<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'receiver_id',
        'message',
        'attachment_type',
        'attachment_path',
        'attachment_name',
        'attachment_size',
        'status',
        'delivered_at',
        'read_at',
        'is_edited',
        'is_deleted',
        'deleted_at_by_sender',
        'deleted_at_by_receiver',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'is_flagged' => 'boolean',
        'deleted_at_by_sender' => 'datetime',
        'deleted_at_by_receiver' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Scopes
     */
    public function scopeForConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeUnread($query, $userId)
    {
        return $query->where('receiver_id', $userId)
            ->where('status', '!=', 'read');
    }

    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
              ->whereNull('deleted_at_by_sender');
        })->orWhere(function ($q) use ($userId) {
            $q->where('receiver_id', $userId)
              ->whereNull('deleted_at_by_receiver');
        });
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Helper Methods
     */
    public function isSentBy($userId)
    {
        return $this->sender_id == $userId;
    }

    public function isReceivedBy($userId)
    {
        return $this->receiver_id == $userId;
    }

    public function isRead()
    {
        return $this->status === 'read';
    }

    public function hasAttachment()
    {
        return !is_null($this->attachment_path);
    }

    public function getAttachmentUrl()
    {
        if ($this->hasAttachment()) {
            return url('storage/' . $this->attachment_path);
        }
        return null;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered()
    {
        if ($this->status === 'sent') {
            $this->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
        }
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        if ($this->status !== 'read') {
            $this->update([
                'status' => 'read',
                'read_at' => now(),
            ]);

            // Create read record
            MessageRead::firstOrCreate([
                'message_id' => $this->id,
                'user_id' => $this->receiver_id,
            ], [
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Delete for user
     */
    public function deleteForUser($userId)
    {
        if ($this->isSentBy($userId)) {
            $this->update(['deleted_at_by_sender' => now()]);
        } else {
            $this->update(['deleted_at_by_receiver' => now()]);
        }

        // If deleted by both, soft delete
        if ($this->deleted_at_by_sender && $this->deleted_at_by_receiver) {
            $this->delete();
        }
    }

    /**
     * Boot method
     */
    protected static function booted()
    {
        static::created(function ($message) {
            // Update conversation last message
            $message->conversation->updateLastMessage($message);
        });
    }
}