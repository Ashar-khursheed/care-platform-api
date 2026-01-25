<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications',
        summary: 'Get all notifications',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request)
    {
        $query = Notification::with('user');

        // Filters
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('is_read')) {
            if ($request->boolean('is_read')) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 50);
        $notifications = $query->paginate($perPage);

        return response()->json($notifications);
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/announcement',
        summary: 'Send announcement',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function sendAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'user_type' => 'nullable|in:client,provider,admin',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $this->notificationService->sendAnnouncement(
            $request->title,
            $request->message,
            $request->user_type
        );

        return response()->json([
            'success' => true,
            'message' => 'Announcement sent successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/send-to-users',
        summary: 'Send to specific users',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function sendToUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $this->notificationService->sendBatch(
            $request->user_ids,
            $request->type,
            $request->title,
            $request->message,
            [
                'priority' => $request->priority ?? 'medium',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications sent to ' . count($request->user_ids) . ' users.',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/notifications/{id}',
        summary: 'Delete notification',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted permanently.',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/notifications/statistics',
        summary: 'Get notification statistics',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function statistics()
    {
        $totalNotifications = Notification::count();
        $unreadNotifications = Notification::unread()->count();
        $readNotifications = Notification::read()->count();

        // Notifications by type
        $byType = Notification::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'type');

        // Notifications by priority
        $byPriority = Notification::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority');

        // Recent notifications (last 7 days grouped by date)
        $recentNotifications = Notification::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Delivery statistics
        $emailsSent = Notification::where('sent_email', true)->count();
        $pushesSent = Notification::where('sent_push', true)->count();
        $smsSent = Notification::where('sent_sms', true)->count();

        // Most engaged users (users with most read notifications)
        $mostEngagedUsers = Notification::selectRaw('user_id, COUNT(*) as notifications_read')
            ->read()
            ->groupBy('user_id')
            ->orderBy('notifications_read', 'desc')
            ->limit(10)
            ->with('user')
            ->get()
            ->map(function ($notification) {
                return [
                    'user_id' => $notification->user_id,
                    'user_name' => $notification->user->first_name . ' ' . $notification->user->last_name,
                    'notifications_read' => $notification->notifications_read,
                ];
            });

        // Read rate
        $readRate = $totalNotifications > 0 
            ? round(($readNotifications / $totalNotifications) * 100, 2) 
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => [
                    'total_notifications' => $totalNotifications,
                    'unread_notifications' => $unreadNotifications,
                    'read_notifications' => $readNotifications,
                    'read_rate' => $readRate . '%',
                ],
                'by_type' => $byType,
                'by_priority' => $byPriority,
                'delivery' => [
                    'emails_sent' => $emailsSent,
                    'pushes_sent' => $pushesSent,
                    'sms_sent' => $smsSent,
                ],
                'recent_notifications' => $recentNotifications,
                'most_engaged_users' => $mostEngagedUsers,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/test',
        summary: 'Test notification',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function test(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = \App\Models\User::find($request->user_id);

        $this->notificationService->send(
            $user,
            'system_announcement',
            'Test Notification',
            'This is a test notification from the admin panel.',
            [
                'priority' => 'low',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent.',
        ]);
    }
}
