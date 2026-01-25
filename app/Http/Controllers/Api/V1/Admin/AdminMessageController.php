<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminMessageController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/messages/conversations',
        summary: 'Get all conversations',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function conversations(Request $request)
    {
        $query = Conversation::with(['user1', 'user2', 'booking', 'lastMessageUser']);

        // Filters
        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('is_blocked')) {
            if ($request->boolean('is_blocked')) {
                $query->blocked();
            } else {
                $query->notBlocked();
            }
        }

        $query->orderBy('last_message_at', 'desc');

        $perPage = $request->get('per_page', 20);
        $conversations = $query->paginate($perPage);

        return ConversationResource::collection($conversations);
    }

    #[OA\Get(
        path: '/api/v1/admin/messages',
        summary: 'Get all messages',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function messages(Request $request)
    {
        $query = Message::with(['sender', 'receiver', 'conversation']);

        // Filters
        if ($request->has('conversation_id')) {
            $query->forConversation($request->conversation_id);
        }

        if ($request->has('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        if ($request->has('receiver_id')) {
            $query->where('receiver_id', $request->receiver_id);
        }

        if ($request->has('is_flagged')) {
            if ($request->boolean('is_flagged')) {
                $query->flagged();
            }
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 50);
        $messages = $query->paginate($perPage);

        return MessageResource::collection($messages);
    }

    #[OA\Get(
        path: '/api/v1/admin/messages/flagged',
        summary: 'Get flagged messages',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function flaggedMessages(Request $request)
    {
        $messages = Message::with(['sender', 'receiver', 'conversation'])
            ->flagged()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    #[OA\Get(
        path: '/api/v1/admin/messages/{id}',
        summary: 'Get message details',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function showMessage($id)
    {
        $message = Message::with(['sender', 'receiver', 'conversation'])
            ->findOrFail($id);

        return new MessageResource($message);
    }

    #[OA\Delete(
        path: '/api/v1/admin/messages/{id}',
        summary: 'Delete message',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted permanently.',
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/messages/{id}/unflag',
        summary: 'Unflag message',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function unflagMessage($id)
    {
        $message = Message::findOrFail($id);

        $message->update([
            'is_flagged' => false,
            'flag_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message unflagged successfully.',
            'data' => new MessageResource($message),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/messages/conversations/{id}/block',
        summary: 'Block conversation',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function blockConversation(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->update([
            'is_blocked' => true,
            'blocked_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation blocked successfully.',
            'data' => new ConversationResource($conversation),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/messages/conversations/{id}/unblock',
        summary: 'Unblock conversation',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 404, description: 'Not found')]
    public function unblockConversation($id)
    {
        $conversation = Conversation::findOrFail($id);

        $conversation->update([
            'is_blocked' => false,
            'blocked_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unblocked successfully.',
            'data' => new ConversationResource($conversation),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/messages/statistics',
        summary: 'Get message statistics',
        tags: ['Admin - Messages'],
        security: [['bearerAuth' => []]]
    )]
    #[OA\Response(response: 200, description: 'Success')]
    public function statistics()
    {
        $totalConversations = Conversation::count();
        $activeConversations = Conversation::where('last_message_at', '>=', now()->subDays(7))->count();
        $blockedConversations = Conversation::blocked()->count();

        $totalMessages = Message::count();
        $flaggedMessages = Message::flagged()->count();
        $messagesWithAttachments = Message::whereNotNull('attachment_path')->count();

        // Recent messages (last 7 days grouped by date)
        $recentMessages = Message::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Most active users
        $mostActiveUsers = Message::selectRaw('sender_id, COUNT(*) as messages_sent')
            ->groupBy('sender_id')
            ->orderBy('messages_sent', 'desc')
            ->limit(10)
            ->with('sender')
            ->get()
            ->map(function ($message) {
                return [
                    'user_id' => $message->sender_id,
                    'user_name' => $message->sender->first_name . ' ' . $message->sender->last_name,
                    'messages_sent' => $message->messages_sent,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => [
                    'total' => $totalConversations,
                    'active' => $activeConversations,
                    'blocked' => $blockedConversations,
                ],
                'messages' => [
                    'total' => $totalMessages,
                    'flagged' => $flaggedMessages,
                    'with_attachments' => $messagesWithAttachments,
                ],
                'recent_messages' => $recentMessages,
                'most_active_users' => $mostActiveUsers,
            ],
        ]);
    }
}
