<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Get user's notifications
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Notification::where('user_id', $user->id);

        // Filter by read status
        if ($request->has('is_read')) {
            if ($request->boolean('is_read')) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->map(function($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'action_url' => $notification->action_url,
                        'is_read' => $notification->is_read,
                        'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
                        'priority' => $notification->priority,
                        'icon' => $notification->getIcon(),
                        'color' => $notification->getColor(),
                        'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                        'data' => $notification->data,
                    ];
                }),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Get recent notifications
     */
    public function recent(Request $request)
    {
        $user = $request->user();
        $days = $request->get('days', 7);
        
        $notifications = Notification::where('user_id', $user->id)
            ->recent($days)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'icon' => $notification->getIcon(),
                    'color' => $notification->getColor(),
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted'
        ]);
    }

    /**
     * Delete all notifications
     */
    public function deleteAll(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'All notifications deleted'
        ]);
    }

    /**
     * Clear all read notifications
     */
    public function clearAll(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
            ->read()
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All read notifications cleared'
        ]);
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        
        $preferences = NotificationPreference::where('user_id', $user->id)->first();

        if (!$preferences) {
            // Create default preferences
            $preferences = NotificationPreference::create([
                'user_id' => $user->id,
                'email_notifications' => true,
                'push_notifications' => true,
                'sms_notifications' => false,
                'notification_types' => [
                    'booking_updates' => true,
                    'payment_updates' => true,
                    'message_updates' => true,
                    'review_updates' => true,
                    'system_updates' => true,
                    'promotional' => false,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'email_notifications' => $preferences->email_notifications,
                'push_notifications' => $preferences->push_notifications,
                'sms_notifications' => $preferences->sms_notifications,
                'notification_types' => $preferences->notification_types ?? [],
            ]
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'email_notifications' => 'sometimes|boolean',
            'push_notifications' => 'sometimes|boolean',
            'sms_notifications' => 'sometimes|boolean',
            'notification_types' => 'sometimes|array',
        ]);

        $preferences = NotificationPreference::updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'email_notifications',
                'push_notifications',
                'sms_notifications',
                'notification_types'
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'data' => $preferences
        ]);
    }

    /**
     * Update notification settings (alias for updatePreferences)
     */
    public function updateSettings(Request $request)
    {
        return $this->updatePreferences($request);
    }

    /**
     * Register device for push notifications
     */
    public function registerDevice(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:ios,android,web',
        ]);

        // Update user's push notification token
        DB::table('user_devices')->updateOrInsert(
            [
                'user_id' => $user->id,
                'device_token' => $request->device_token
            ],
            [
                'device_type' => $request->device_type,
                'is_active' => true,
                'last_used_at' => now(),
                'updated_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device registered for push notifications'
        ]);
    }

    /**
     * Unregister device from push notifications
     */
    public function unregisterDevice(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'device_token' => 'required|string',
        ]);

        DB::table('user_devices')
            ->where('user_id', $user->id)
            ->where('device_token', $request->device_token)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device unregistered from push notifications'
        ]);
    }
}
