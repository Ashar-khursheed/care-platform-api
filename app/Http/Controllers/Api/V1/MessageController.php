<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MessageStoreRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
        /**
 *     @OA\Get(
 *         path="/api/v1/messages/conversations",
 *         summary="Get user conversations",
 *         tags={"Messages"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::forUser($user->id)
            ->with(['user1', 'user2', 'booking', 'lastMessageUser'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return ConversationResource::collection($conversations);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/conversations/{id}/messages",
     *     summary="Get messages for a conversation",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function messages(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization: Must be participant
        if (!$conversation->isParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this conversation.',
            ], 403);
        }

        // Check if blocked
        if ($conversation->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'This conversation is blocked.',
            ], 403);
        }

        $messages = Message::forConversation($conversationId)
            ->with(['sender', 'receiver'])
            ->visibleTo($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        // Mark messages as read
        $conversation->markAsRead($request->user()->id);

        return MessageResource::collection($messages);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/messages/send",
     *     summary="Send a message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"receiver_id", "message"},
     *                 @OA\Property(property="receiver_id", type="integer"),
     *                 @OA\Property(property="booking_id", type="integer"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="attachment", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Message sent"),
     *     @OA\Response(response=403, description="Blocked")
     * )
     */
    public function send(MessageStoreRequest $request)
    {
        $user = $request->user();

        // Find or create conversation
        $conversation = Conversation::findOrCreate(
            $user->id,
            $request->receiver_id,
            $request->booking_id
        );

        // Check if conversation is blocked
        if ($conversation->isBlocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send message. Conversation is blocked.',
            ], 403);
        }

        // Handle attachment
        $attachmentPath = null;
        $attachmentType = null;
        $attachmentName = null;
        $attachmentSize = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('message_attachments', 'public');
            
            $attachmentPath = $path;
            $attachmentType = $this->getAttachmentType($file->getMimeType());
            $attachmentName = $file->getClientOriginalName();
            $attachmentSize = round($file->getSize() / 1024); // Convert to KB
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'attachment_type' => $attachmentType,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_size' => $attachmentSize,
            'status' => 'sent',
        ]);

        // TODO: Broadcast message via Pusher/WebSocket
        // event(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => new MessageResource($message),
        ], 201);
    }

        /**
 *     @OA\Put(
 *         path="/api/v1/messages/{id}/read",
 *         summary="Mark message as read",
 *         tags={"Messages"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The id of the resource",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function markAsRead(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization
        if (!$conversation->isParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $conversation->markAsRead($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read.',
        ]);
    }

    /**
     * Delete message
     * USED BY: Clients & Providers
     * Can delete their own sent or received messages
     */
    public function deleteMessage(Request $request, $messageId)
    {
        $message = Message::findOrFail($messageId);

        // Authorization: Must be sender or receiver
        if (!$message->isSentBy($request->user()->id) && 
            !$message->isReceivedBy($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this message.',
            ], 403);
        }

        $message->deleteForUser($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }

    /**
     * Block conversation
     * USED BY: Clients & Providers
     * Either party can block
     */
    public function blockConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization
        if (!$conversation->isParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $conversation->update([
            'is_blocked' => true,
            'blocked_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation blocked successfully.',
        ]);
    }

    /**
     * Unblock conversation
     * USED BY: Clients & Providers
     * Only person who blocked can unblock
     */
    public function unblockConversation(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Authorization: Must be the one who blocked
        if ($conversation->blocked_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to unblock this conversation.',
            ], 403);
        }

        $conversation->update([
            'is_blocked' => false,
            'blocked_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unblocked successfully.',
        ]);
    }

    /**
     * Flag message as inappropriate
     * USED BY: Clients & Providers
     */
    public function flagMessage(Request $request, $messageId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $message = Message::findOrFail($messageId);

        $message->update([
            'is_flagged' => true,
            'flag_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message flagged for review.',
        ]);
    }

        /**
 *     @OA\Get(
 *         path="/api/v1/messages/unread",
 *         summary="Get unread count",
 *         tags={"Messages"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Resource not found"
 *     )
 *     )
 */
    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;

        $unreadCount = Message::where('receiver_id', $userId)
            ->where('status', '!=', 'read')
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/search",
     *     summary="Search messages",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search term",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $userId = $request->user()->id;

        $messages = Message::where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->where('message', 'like', '%' . $request->query . '%')
            ->with(['sender', 'receiver', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return MessageResource::collection($messages);
    }

    /**
     * Get attachment type from MIME type
     * HELPER METHOD
     */
    protected function getAttachmentType($mimeType)
    {
        if (str_contains($mimeType, 'image')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video')) {
            return 'video';
        } elseif (str_contains($mimeType, 'audio')) {
            return 'audio';
        } else {
            return 'document';
        }
    }
}
