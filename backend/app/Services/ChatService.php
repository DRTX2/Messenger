<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    /**
     * Get user's inbox (list of conversations)
     * This mimics WhatsApp/Messenger home screen
     */
    public function getConversations(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::query()
            ->whereHas('participants', function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['latestMessage', 'participants' => function($q) use ($userId) {
                // Determine the "other" user for naming purposes in frontend
                $q->where('user_id', '!=', $userId); 
            }])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    /**
     * Get or Create a private conversation between two users.
     */
    public function getPrivateConversation(int $authUserId, int $otherUserId): Conversation
    {
        if ($authUserId === $otherUserId) {
            throw new BusinessException('Cannot chat with yourself', 400);
        }

        // 1. Check if conversation already exists
        // We look for a conversation with exactly these 2 participants and is_group = false.
        $conversation = Conversation::where('is_group', false)
            ->whereHas('participants', function ($q) use ($authUserId) {
                $q->where('user_id', $authUserId);
            })
            ->whereHas('participants', function ($q) use ($otherUserId) {
                $q->where('user_id', $otherUserId);
            })
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // 2. Create new if not exists
        return DB::transaction(function () use ($authUserId, $otherUserId) {
            $conversation = Conversation::create([
                'uuid' => (string) Str::uuid(),
                'is_group' => false,
                'created_by' => $authUserId,
                'last_message_at' => now(),
            ]);

            // Add both users
            $conversation->participants()->attach([
                $authUserId => ['formatted_last_read_at' => now()],
                $otherUserId => ['formatted_last_read_at' => null], // Hasn't seen it yet
            ]);

            return $conversation;
        });
    }

    /**
     * Get messages for a specific conversation (Thread View)
     */
    public function getConversationMessages(int $userId, Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        // Security check: Is user part of this conversation?
        $isParticipant = $conversation->participants()->where('user_id', $userId)->exists();
        
        if (! $isParticipant) {
            throw new BusinessException('You are not a participant in this conversation.', 403);
        }

        return $conversation->messages()
            ->with(['sender:id,name,email', 'attachments', 'parent'])
            ->orderByDesc('created_at') // Latest first for chat UI (usually reversed in frontend)
            ->paginate($perPage);
    }

    /**
     * Send a message to a USER (Legacy/Direct wrapper)
     * This maintains the existing controller signature but uses the new engine.
     */
    public function sendMessageToUser(int $senderId, int $receiverId, string $content, ?array $attachmentIds = []): Message
    {
        // 1. Ensure conversation exists
        $conversation = $this->getPrivateConversation($senderId, $receiverId);

        // 2. Create Message
        return DB::transaction(function () use ($conversation, $senderId, $content, $attachmentIds) {
            $message = $conversation->messages()->create([
                'sender_id' => $senderId,
                'content' => $content,
                'type' => empty($attachmentIds) ? 'text' : 'file', 
            ]);

            // 3. Link Attachments
            if (!empty($attachmentIds)) {
                (new AttachmentService())->linkAttachmentsToMessage($message->id, $attachmentIds);
            }

            // 4. Update Conversation Timestamp (to bump it to top of inbox)
            $conversation->update(['last_message_at' => now()]);

            $message->load(['sender', 'conversation', 'attachments']);
            
            // 5. Broadcast Real-time Event
            \App\Events\MessageSent::dispatch($message);

            return $message;
        });
    }

    /**
     * Mark conversation as read for a user
     */
    public function markAsRead(int $userId, int $conversationId): void
    {
        DB::table('conversation_participants')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['formatted_last_read_at' => now()]);
        
        // Also update specific messages if needed, though conversation-level read receipt is often enough for lists
    }
    
    // --- Legacy Adapter Methods (keeping signatures valid for ChatController if possible) ---

    // Note: Adjusted signature to match new controller usage
    public function sendMessage(int $senderId, int $receiverId, string $content, array $attachments = []): Message
    {
       return $this->sendMessageToUser($senderId, $receiverId, $content, $attachments);
    }

    public function getUsers(int $userId, int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        return User::where('id', '!=', $userId)
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getConversation(int $authUserId, int $otherUserId, int $perPage = 20): LengthAwarePaginator
    {
        $conversation = $this->getPrivateConversation($authUserId, $otherUserId);
        return $this->getConversationMessages($authUserId, $conversation, $perPage);
    }

    public function getUnreadCount(int $userId): int
    {
        // Count conversations where last_message_at > formatted_last_read_at
        // deeper query needed, assuming simplistic approach for now
        return 0; // TODO: Implement robust unread count
    }
    
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = Message::find($messageId);
        if (!$message || $message->sender_id !== $userId) {
            return false;
        }
        return (bool) $message->delete();
    }

    public function clearConversation(int $authUserId, int $otherUserId): bool
    {
        $conversation = $this->getPrivateConversation($authUserId, $otherUserId);
        // Only clear for ME? Or delete all?
        // Usually "Clear Chat" means "Delete for Me".
        // For now, let's keep the old aggressive behavior but scoped to the conversation.
        return (bool) $conversation->messages()->delete(); 
    }

    public function toggleFavorite(int $messageId, int $userId): Message
    {
        $message = Message::findOrFail($messageId);
        $message->is_favorite = !$message->is_favorite;
        $message->save();
        return $message;
    }
}
