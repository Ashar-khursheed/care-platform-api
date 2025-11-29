<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'action_url',
        'data',
        'is_read',
        'read_at',
        'sent_in_app',
        'sent_email',
        'sent_push',
        'sent_sms',
        'email_sent_at',
        'push_sent_at',
        'sms_sent_at',
        'priority',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_in_app' => 'boolean',
        'sent_email' => 'boolean',
        'sent_push' => 'boolean',
        'sent_sms' => 'boolean',
        'email_sent_at' => 'datetime',
        'push_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relationship to related entity
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Helper Methods
     */
    public function isRead()
    {
        return $this->is_read;
    }

    public function isUnread()
    {
        return !$this->is_read;
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get notification icon based on type
     */
    public function getIcon()
    {
        $icons = [
            'booking_created' => '📅',
            'booking_accepted' => '✅',
            'booking_rejected' => '❌',
            'booking_cancelled' => '🚫',
            'booking_completed' => '✓',
            'payment_received' => '💰',
            'payment_failed' => '❌',
            'payment_refunded' => '↩️',
            'payout_processed' => '💵',
            'message_received' => '💬',
            'review_received' => '⭐',
            'review_response' => '💭',
            'document_approved' => '✅',
            'document_rejected' => '❌',
            'listing_approved' => '✅',
            'listing_rejected' => '❌',
            'system_announcement' => '📢',
            'promotional' => '🎁',
        ];

        return $icons[$this->type] ?? '🔔';
    }

    /**
     * Get notification color based on type
     */
    public function getColor()
    {
        $colors = [
            'booking_created' => 'blue',
            'booking_accepted' => 'green',
            'booking_rejected' => 'red',
            'booking_cancelled' => 'orange',
            'booking_completed' => 'green',
            'payment_received' => 'green',
            'payment_failed' => 'red',
            'payment_refunded' => 'orange',
            'payout_processed' => 'green',
            'message_received' => 'blue',
            'review_received' => 'yellow',
            'review_response' => 'blue',
            'document_approved' => 'green',
            'document_rejected' => 'red',
            'listing_approved' => 'green',
            'listing_rejected' => 'red',
            'system_announcement' => 'purple',
            'promotional' => 'pink',
        ];

        return $colors[$this->type] ?? 'gray';
    }
}