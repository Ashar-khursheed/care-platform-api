<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user1_id',
        'user2_id',
        'booking_id',
        'last_message',
        'last_message_user_id',
        'last_message_at',
        'is_blocked',
        'blocked_by',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessageUser()
    {
        return $this->belongsTo(User::class, 'last_message_user_id');
    }

    public function blockedByUser()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user1_id', $userId)
              ->orWhere('user2_id', $userId);
        });
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Helper Methods
     */
    public function isParticipant($userId)
    {
        return in_array($userId, [$this->user1_id, $this->user2_id]);
    }

    public function getOtherUser($userId)
    {
        if ($this->user1_id == $userId) {
            return $this->user2;
        }
        return $this->user1;
    }

    public function getOtherUserId($userId)
    {
        if ($this->user1_id == $userId) {
            return $this->user2_id;
        }
        return $this->user1_id;
    }

    public function isBlocked()
    {
        return $this->is_blocked;
    }

    public function isBlockedBy($userId)
    {
        return $this->is_blocked && $this->blocked_by == $userId;
    }

    public function getUnreadCount($userId)
    {
        return $this->messages()
            ->where('receiver_id', $userId)
            ->where('status', '!=', 'read')
            ->count();
    }

    /**
     * Update last message info
     */
    public function updateLastMessage(Message $message)
    {
        $this->update([
            'last_message' => $message->message,
            'last_message_user_id' => $message->sender_id,
            'last_message_at' => $message->created_at,
        ]);
    }

    /**
     * Find or create conversation between two users
     */
    public static function findOrCreate($user1Id, $user2Id, $bookingId = null)
    {
        // Ensure consistent ordering (smaller ID first)
        $ids = [$user1Id, $user2Id];
        sort($ids);

        $conversation = self::where('user1_id', $ids[0])
            ->where('user2_id', $ids[1])
            ->when($bookingId, function ($query) use ($bookingId) {
                $query->where('booking_id', $bookingId);
            })
            ->first();

        if (!$conversation) {
            $conversation = self::create([
                'user1_id' => $ids[0],
                'user2_id' => $ids[1],
                'booking_id' => $bookingId,
            ]);
        }

        return $conversation;
    }

    /**
     * Mark all messages as read for user
     */
    public function markAsRead($userId)
    {
        $this->messages()
            ->where('receiver_id', $userId)
            ->where('status', '!=', 'read')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }
}