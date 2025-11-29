<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'enabled',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default preferences for all notification types
     */
    public static function getDefaults()
    {
        return [
            // Booking notifications
            'booking_created' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'booking_accepted' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'booking_rejected' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'booking_cancelled' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'booking_completed' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // Payment notifications
            'payment_received' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'payment_failed' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => true,
            ],
            'payment_refunded' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'payout_processed' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // Message notifications
            'message_received' => [
                'enabled' => true,
                'email_enabled' => false,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // Review notifications
            'review_received' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'review_response' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // Document notifications
            'document_approved' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'document_rejected' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // Listing notifications
            'listing_approved' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'listing_rejected' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            
            // System notifications
            'system_announcement' => [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ],
            'promotional' => [
                'enabled' => true,
                'email_enabled' => false,
                'push_enabled' => false,
                'sms_enabled' => false,
            ],
        ];
    }

    /**
     * Initialize default preferences for a user
     */
    public static function initializeForUser($userId)
    {
        $defaults = self::getDefaults();

        foreach ($defaults as $type => $settings) {
            self::firstOrCreate(
                [
                    'user_id' => $userId,
                    'notification_type' => $type,
                ],
                $settings
            );
        }
    }
}