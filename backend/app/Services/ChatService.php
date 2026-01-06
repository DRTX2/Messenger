<?php

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Get all users except the authenticated user
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsers(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return User::where('id', '!=', $userId)
            ->select(['id', 'name', 'email', 'created_at'])
            ->withCount([
                'sentMessages as unread_messages' => function ($query) use ($userId) {
                    $query->where('receiver_id', $userId)
                        ->whereNull('read_at');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get conversation between two users with pagination
     *
     * @param int $authUserId
     * @param int $otherUserId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getConversation(int $authUserId, int $otherUserId, int $perPage = 20): LengthAwarePaginator
    {
        // Validate that other user exists
        if (!User::find($otherUserId)) {
            throw new \Exception('User not found', 404);
        }

        // Prevent accessing same user
        if ($authUserId === $otherUserId) {
            throw new \Exception('Cannot chat with yourself', 400);
        }

        return Message::query()
            ->with(['sender:id,name,email', 'receiver:id,name,email'])
            ->where(function ($query) use ($authUserId, $otherUserId) {
                $query->where('sender_id', $authUserId)
                    ->where('receiver_id', $otherUserId);
            })
            ->orWhere(function ($query) use ($authUserId, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                    ->where('receiver_id', $authUserId);
            })
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Send a message from one user to another
     *
     * @param int $senderId
     * @param int $receiverId
     * @param string $content
     * @return Message
     */
    public function sendMessage(int $senderId, int $receiverId, string $content): Message
    {
        // Validate that receiver exists
        if (!User::find($receiverId)) {
            throw new \Exception('Recipient not found', 404);
        }

        // Prevent sending messages to yourself
        if ($senderId === $receiverId) {
            throw new \Exception('Cannot send messages to yourself', 400);
        }

        // Use transaction to ensure data consistency
        return DB::transaction(function () use ($senderId, $receiverId, $content) {
            $message = Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'content' => $content,
            ]);

            // Load relationships for response
            return $message->load(['sender:id,name,email', 'receiver:id,name,email']);
        });
    }

    /**
     * Mark messages as read
     *
     * @param int $authUserId
     * @param int $otherUserId
     * @return void
     */
    public function markAsRead(int $authUserId, int $otherUserId): void
    {
        Message::query()
            ->where('receiver_id', $authUserId)
            ->where('sender_id', $otherUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread message count for a user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return Message::query()
            ->where('receiver_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Delete a message (only the sender can delete)
     *
     * @param int $messageId
     * @param int $userId
     * @return bool
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = Message::find($messageId);

        if (!$message) {
            throw new \Exception('Message not found', 404);
        }

        if ($message->sender_id !== $userId) {
            throw new \Exception('Unauthorized to delete this message', 403);
        }

        return $message->delete();
    }

    /**
     * Clear all messages in a conversation
     *
     * @param int $authUserId
     * @param int $otherUserId
     * @return bool
     */
    public function clearConversation(int $authUserId, int $otherUserId): bool
    {
        return Message::where(function ($query) use ($authUserId, $otherUserId) {
            $query->where('sender_id', $authUserId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($query) use ($authUserId, $otherUserId) {
            $query->where('sender_id', $otherUserId)->where('receiver_id', $authUserId);
        })->delete();
    }

    /**
     * Toggle favorite status of a message
     *
     * @param int $messageId
     * @param int $userId
     * @return Message
     */
    public function toggleFavorite(int $messageId, int $userId): Message
    {
        $message = Message::find($messageId);

        if (!$message) {
            throw new \Exception('Message not found', 404);
        }

        if ($message->sender_id !== $userId && $message->receiver_id !== $userId) {
            throw new \Exception('Unauthorized', 403);
        }

        $message->is_favorite = !$message->is_favorite;
        $message->save();

        return $message;
    }
}
