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

        // Dummy data for integration if empty
        if ($notifications->isEmpty()) {
            $dummyData = collect([
                [
                    'id' => 1,
                    'user_id' => 101,
                    'type' => 'booking_created',
                    'title' => 'New Booking Request',
                    'message' => 'You have a new booking request from John Doe.',
                    'is_read' => false,
                    'created_at' => now()->subMinutes(5)->toIso8601String(),
                    'priority' => 'high',
                    'data' => null,
                    'user' => [
                        'id' => 101,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john.doe@example.com',
                        'avatar_url' => 'https://ui-avatars.com/api/?name=John+Doe',
                    ]
                ],
                [
                    'id' => 2,
                    'user_id' => 102,
                    'type' => 'payment_received',
                    'title' => 'Payment Received',
                    'message' => 'Payment of $150.00 received for booking #1234.',
                    'is_read' => true,
                    'created_at' => now()->subHours(2)->toIso8601String(),
                    'priority' => 'medium',
                    'data' => ['amount' => 150.00, 'currency' => 'USD'],
                    'user' => [
                        'id' => 102,
                        'first_name' => 'Sarah',
                        'last_name' => 'Smith',
                        'email' => 'sarah.smith@example.com',
                        'avatar_url' => 'https://ui-avatars.com/api/?name=Sarah+Smith',
                    ]
                ],
                [
                    'id' => 3,
                    'user_id' => 103,
                    'type' => 'system_announcement',
                    'title' => 'System Maintenance',
                    'message' => 'Scheduled maintenance will occur on Sunday at 2 AM.',
                    'is_read' => false,
                    'created_at' => now()->subDays(1)->toIso8601String(),
                    'priority' => 'low',
                    'data' => null,
                    'user' => [
                        'id' => 103,
                        'first_name' => 'Admin',
                        'last_name' => 'User',
                        'email' => 'admin@example.com',
                        'avatar_url' => 'https://ui-avatars.com/api/?name=Admin+User',
                    ]
                ],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'notifications' => $dummyData,
                    'pagination' => [
                        'total' => $dummyData->count(),
                        'per_page' => $perPage,
                        'current_page' => 1,
                        'last_page' => 1,
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications->items(),
                'pagination' => [
                    'total' => $notifications->total(),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                ]
            ]
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/announcement',
        summary: 'Send announcement',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'message'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Maintenance Alert'),
                new OA\Property(property: 'message', type: 'string', example: 'System will be down for maintenance.'),
                new OA\Property(property: 'user_type', type: 'string', enum: ['client', 'provider', 'admin'], nullable: true, example: 'provider'),
                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], nullable: true, example: 'high')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 500, description: 'Server Error')]
    public function sendAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'user_type' => 'nullable|in:client,provider,admin',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        try {
            $this->notificationService->sendAnnouncement(
                $request->title,
                $request->message,
                $request->user_type
            );

            return response()->json([
                'success' => true,
                'message' => 'Announcement sent successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send announcement: ' . $e->getMessage(),
                // 'trace' => $e->getTraceAsString() // debug use only
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/send-to-users',
        summary: 'Send to specific users',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['user_ids', 'type', 'title', 'message'],
            properties: [
                new OA\Property(property: 'user_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                new OA\Property(property: 'type', type: 'string', example: 'promotional'),
                new OA\Property(property: 'title', type: 'string', example: 'Test Notification'),
                new OA\Property(property: 'message', type: 'string', example: 'This is a test notification.'),
                new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], nullable: true, example: 'medium')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Created successfully')]
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

        try {
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
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage(),
            ], 500);
        }
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
        try {
            $notification = Notification::findOrFail($id);
            $notification->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted permanently.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification.',
            ], 500);
        }
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
        try {
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
                        'user_name' => $notification->user ? ($notification->user->first_name . ' ' . $notification->user->last_name) : 'Unknown User',
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
        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/notifications/test',
        summary: 'Test notification',
        tags: ['Admin - Notifications'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['user_id'],
            properties: [
                new OA\Property(property: 'user_id', type: 'integer', example: 1)
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'Successful operation')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Resource not found')]
    public function test(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
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
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage(),
            ], 500);
        }
    }
}
