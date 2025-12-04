<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class AdminMessageController extends Controller
{
    /**
     * Get all conversations
     */
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

    /**
     * Get all messages
     */
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

    /**
     * Get flagged messages
     */
    public function flaggedMessages(Request $request)
    {
        $messages = Message::with(['sender', 'receiver', 'conversation'])
            ->flagged()
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return MessageResource::collection($messages);
    }

    /**
     * View message details
     */
    public function showMessage($id)
    {
        $message = Message::with(['sender', 'receiver', 'conversation'])
            ->findOrFail($id);

        return new MessageResource($message);
    }

    /**
     * Delete message (admin)
     */
    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);
        $message->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted permanently.',
        ]);
    }

    /**
     * Unflag message
     */
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

    /**
     * Block conversation (admin)
     */
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

    /**
     * Unblock conversation (admin)
     */
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

    /**
     * Get messaging statistics
     */
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