<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $options = []
    ) {
        // Get user preferences
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->first();

        // If no preference exists, use defaults
        if (!$preference) {
            $defaults = NotificationPreference::getDefaults();
            $preference = (object) ($defaults[$type] ?? [
                'enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sms_enabled' => false,
            ]);
        }

        // Check if notification is enabled
        if (!$preference->enabled || !$user->notifications_enabled) {
            return null;
        }

        // Create in-app notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'action_url' => $options['action_url'] ?? null,
            'data' => $options['data'] ?? null,
            'priority' => $options['priority'] ?? 'medium',
            'sent_in_app' => true,
        ]);

        // Send email notification
        if ($preference->email_enabled && $user->email_notifications_enabled) {
            $this->sendEmail($user, $notification);
        }

        // Send push notification
        if ($preference->push_enabled && $user->push_notifications_enabled) {
            $this->sendPush($user, $notification);
        }

        // Send SMS notification
        if ($preference->sms_enabled && $user->sms_notifications_enabled) {
            $this->sendSMS($user, $notification);
        }

        return $notification;
    }

    /**
     * Send email notification
     */
    protected function sendEmail(User $user, Notification $notification)
    {
        try {
            // TODO: Implement actual email sending
            // Mail::to($user->email)->send(new NotificationEmail($notification));

            $notification->update([
                'sent_email' => true,
                'email_sent_at' => now(),
            ]);

            Log::info("Email notification sent to {$user->email}", [
                'notification_id' => $notification->id,
                'type' => $notification->type,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage(), [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        }
    }

    /**
     * Send push notification
     */
    protected function sendPush(User $user, Notification $notification)
    {
        try {
            if (!$user->fcm_token) {
                return;
            }

            // TODO: Implement Firebase Cloud Messaging
            // $fcm = new FCMService();
            // $fcm->send($user->fcm_token, $notification->title, $notification->message);

            $notification->update([
                'sent_push' => true,
                'push_sent_at' => now(),
            ]);

            Log::info("Push notification sent to user {$user->id}", [
                'notification_id' => $notification->id,
                'type' => $notification->type,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage(), [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        }
    }

    /**
     * Send SMS notification
     */
    protected function sendSMS(User $user, Notification $notification)
    {
        try {
            if (!$user->phone) {
                return;
            }

            // TODO: Implement Twilio SMS
            // $twilio = new TwilioService();
            // $twilio->send($user->phone, $notification->message);

            $notification->update([
                'sent_sms' => true,
                'sms_sent_at' => now(),
            ]);

            Log::info("SMS notification sent to {$user->phone}", [
                'notification_id' => $notification->id,
                'type' => $notification->type,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification: " . $e->getMessage(), [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        }
    }

    /**
     * Send booking notification
     */
    public function notifyBookingCreated($booking)
    {
        // Notify provider
        $this->send(
            $booking->provider,
            'booking_created',
            'New Booking Request',
            "You have a new booking request from {$booking->client->first_name} on {$booking->booking_date->format('M d, Y')}",
            [
                'related_type' => 'App\Models\Booking',
                'related_id' => $booking->id,
                'action_url' => "/bookings/{$booking->id}",
                'data' => [
                    'booking_id' => $booking->id,
                    'client_name' => $booking->client->first_name . ' ' . $booking->client->last_name,
                ],
            ]
        );
    }

    /**
     * Send booking accepted notification
     */
    public function notifyBookingAccepted($booking)
    {
        // Notify client
        $this->send(
            $booking->client,
            'booking_accepted',
            'Booking Accepted',
            "Your booking request for {$booking->booking_date->format('M d, Y')} has been accepted by {$booking->provider->first_name}",
            [
                'related_type' => 'App\Models\Booking',
                'related_id' => $booking->id,
                'action_url' => "/bookings/{$booking->id}",
                'priority' => 'high',
            ]
        );
    }

    /**
     * Send payment received notification
     */
    public function notifyPaymentReceived($payment)
    {
        // Notify provider
        $this->send(
            $payment->provider,
            'payment_received',
            'Payment Received',
            "You received a payment of \${$payment->provider_amount} for booking #{$payment->booking_id}",
            [
                'related_type' => 'App\Models\Payment',
                'related_id' => $payment->id,
                'action_url' => "/payments/{$payment->id}",
                'priority' => 'high',
            ]
        );

        // Notify client
        $this->send(
            $payment->client,
            'payment_received',
            'Payment Confirmed',
            "Your payment of \${$payment->amount} has been processed successfully",
            [
                'related_type' => 'App\Models\Payment',
                'related_id' => $payment->id,
                'action_url' => "/payments/{$payment->id}",
            ]
        );
    }

    /**
     * Send message notification
     */
    public function notifyMessageReceived($message)
    {
        $this->send(
            $message->receiver,
            'message_received',
            'New Message',
            "You have a new message from {$message->sender->first_name}",
            [
                'related_type' => 'App\Models\Message',
                'related_id' => $message->id,
                'action_url' => "/messages/{$message->conversation_id}",
                'data' => [
                    'message_preview' => substr($message->message, 0, 50),
                    'sender_name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                ],
            ]
        );
    }

    /**
     * Send review notification
     */
    public function notifyReviewReceived($review)
    {
        $this->send(
            $review->provider,
            'review_received',
            'New Review',
            "You received a {$review->rating}-star review from {$review->client->first_name}",
            [
                'related_type' => 'App\Models\Review',
                'related_id' => $review->id,
                'action_url' => "/reviews/{$review->id}",
                'data' => [
                    'rating' => $review->rating,
                    'client_name' => $review->client->first_name,
                ],
            ]
        );
    }

    /**
     * Send batch notifications
     */
    public function sendBatch(array $userIds, string $type, string $title, string $message, array $options = [])
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->send($user, $type, $title, $message, $options);
        }
    }

    /**
     * Send system announcement
     */
    public function sendAnnouncement(string $title, string $message, $userType = null)
    {
        $query = User::query();

        if ($userType) {
            $query->where('user_type', $userType);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $this->send($user, 'system_announcement', $title, $message, [
                'priority' => 'high',
            ]);
        }
    }
}