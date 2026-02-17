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
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Messages",
 *     description="Messaging system endpoints"
 * )
 */
class MessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/messages/conversations",
     *     summary="Get user conversations",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ConversationResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::forUser($userId)
            ->with(['user1', 'user2', 'booking']) // Eager load relationships
            ->orderBy('last_message_at', 'desc')
            ->paginate(10);

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
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MessageResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Conversation not found")
     * )
     */
    public function messages(Request $request, $conversationId)
    {
        $userId = $request->user()->id;
        
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!$conversation->isParticipant($userId)) {
            return response()->json(['message' => 'You are not a participant in this conversation'], 403);
        }

        // Mark messages as read
        $conversation->markAsRead($userId);

        $messages = $conversation->messages()
            ->visibleTo($userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

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
     *                 required={"receiver_id"},
     *                 @OA\Property(property="receiver_id", type="integer", description="Receiver User ID"),
     *                 @OA\Property(property="message", type="string", description="Message content (required if no attachment)"),
     *                 @OA\Property(property="booking_id", type="integer", description="Associated Booking ID (optional)"),
     *                 @OA\Property(property="attachment", type="string", format="binary", description="File attachment (optional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201, 
     *         description="Message sent",
     *         @OA\JsonContent(ref="#/components/schemas/MessageResource")
     *     ),
     *     @OA\Response(response=403, description="Blocked or Unauthorized"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function send(MessageStoreRequest $request)
    {
        $userId = $request->user()->id;
        $receiverId = $request->receiver_id;
        
        // Find or create conversation
        $conversation = Conversation::findOrCreate($userId, $receiverId, $request->booking_id);

        // Check if blocked
        if ($conversation->isBlocked()) {
            return response()->json(['message' => 'You cannot reply to this conversation.'], 403);
        }

        // Handle Attachment
        $attachmentPath = null;
        $attachmentType = null;
        $attachmentName = null;
        $attachmentSize = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();

            $type = $this->getAttachmentType($mimeType);
            $folder = $this->getAttachmentTypeFolder($type);
            
            $path = $file->storeAs("messages/{$conversation->id}/{$folder}", Str::random(40) . '.' . $file->getClientOriginalExtension(), 'public');

            $attachmentPath = $path;
            $attachmentType = $type;
            $attachmentName = $originalName;
            $attachmentSize = $size;
        }

        // Create Message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'attachment_size' => $attachmentSize,
            'status' => 'sent',
        ]);

        return new MessageResource($message);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/messages/conversations/{id}/read",
     *     summary="Mark conversation as read",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Marked as read"),
     *     @OA\Response(response=404, description="Conversation not found")
     * )
     */
    public function markAsRead(Request $request, $conversationId)
    {
        $userId = $request->user()->id;
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!$conversation->isParticipant($userId)) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->markAsRead($userId);

        return response()->json(['message' => 'Conversation marked as read']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/messages/{id}",
     *     summary="Delete a message",
     *     description="Deletes a message for the authenticated user only",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Message deleted"),
     *     @OA\Response(response=404, description="Message not found"),
     *     @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function deleteMessage(Request $request, $messageId)
    {
        $userId = $request->user()->id;
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        if (!$message->isSentBy($userId) && !$message->isReceivedBy($userId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->deleteForUser($userId);

        return response()->json(['message' => 'Message deleted']);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/messages/conversations/{id}/block",
     *     summary="Block a conversation",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Conversation blocked"),
     *     @OA\Response(response=404, description="Conversation not found")
     * )
     */
    public function blockConversation(Request $request, $conversationId)
    {
        $userId = $request->user()->id;
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!$conversation->isParticipant($userId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->update([
            'is_blocked' => true,
            'blocked_by' => $userId
        ]);

        return response()->json(['message' => 'Conversation blocked']);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/messages/conversations/{id}/unblock",
     *     summary="Unblock a conversation",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Conversation ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Conversation unblocked"),
     *     @OA\Response(response=403, description="Cannot unblock if not the blocker")
     * )
     */
    public function unblockConversation(Request $request, $conversationId)
    {
        $userId = $request->user()->id;
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        if (!$conversation->isParticipant($userId)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($conversation->isBlocked() && $conversation->blocked_by != $userId) {
            return response()->json(['message' => 'You cannot unblock this conversation'], 403);
        }

        $conversation->update([
            'is_blocked' => false,
            'blocked_by' => null
        ]);

        return response()->json(['message' => 'Conversation unblocked']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/messages/{id}/flag",
     *     summary="Flag a message",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Message ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", description="Reason for flagging")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Message flagged")
     * )
     */
    public function flagMessage(Request $request, $messageId)
    {
        $userId = $request->user()->id;
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json(['message' => 'Message not found'], 404);
        }

        // Any user can flag any message they can see
        if (!$message->isSentBy($userId) && !$message->isReceivedBy($userId)) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message->update([
            'is_flagged' => true,
            'flag_reason' => $request->input('reason', 'Inappropriate content'),
        ]);

        return response()->json(['message' => 'Message flagged for review']);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/messages/unread-count",
     *     summary="Get global unread message count",
     *     tags={"Messages"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="count", type="integer")
     *         )
     *     )
     * )
     */
    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;
        
        // Count unread messages across all conversations
        $count = Message::unread($userId)->count();

        return response()->json(['count' => $count]);
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
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/MessageResource"))
     *     )
     * )
     */
    public function search(Request $request)
    {
        $userId = $request->user()->id;
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $messages = Message::visibleTo($userId)
            ->where('message', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return MessageResource::collection($messages);
    }

    /**
     * Helper: Get attachment type from MIME type
     */
    private function getAttachmentType($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        return 'document';
    }

    /**
     * Helper: Get folder name for attachment type
     */
    private function getAttachmentTypeFolder($type)
    {
        return match ($type) {
            'image' => 'images',
            'video' => 'videos',
            'audio' => 'audio',
            default => 'documents',
        };
    }
}
