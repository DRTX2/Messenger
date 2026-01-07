<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Message;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Get all users except the authenticated user
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
     */
    public function getConversation(int $authUserId, int $otherUserId, int $perPage = 20): LengthAwarePaginator
    {
        $userExists = User::where('id', $otherUserId)->exists();
        
        if (!$userExists) {
            throw new BusinessException('User not found', 404);
        }

        if ($authUserId === $otherUserId) {
            throw new BusinessException('Cannot chat with yourself', 400);
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
     */
    public function sendMessage(int $senderId, int $receiverId, string $content): Message
    {
        if (!User::where('id', $receiverId)->exists()) {
            throw new BusinessException('Recipient not found', 404);
        }

        if ($senderId === $receiverId) {
            throw new BusinessException('Cannot send messages to yourself', 400);
        }

        return DB::transaction(function () use ($senderId, $receiverId, $content) {
            $message = Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'content' => $content,
            ]);

            return $message->load(['sender:id,name,email', 'receiver:id,name,email']);
        });
    }

    /**
     * Mark messages as read
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
     */
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $message = Message::find($messageId);

        if (!$message) {
            throw new BusinessException('Message not found', 404);
        }

        if ($message->sender_id !== $userId) {
            throw new BusinessException('Unauthorized to delete this message', 403);
        }

        return (bool) $message->delete();
    }

    /**
     * Clear all messages in a conversation
     */
    public function clearConversation(int $authUserId, int $otherUserId): bool
    {
        return (bool) Message::where(function ($query) use ($authUserId, $otherUserId) {
            $query->where('sender_id', $authUserId)->where('receiver_id', $otherUserId);
        })->orWhere(function ($query) use ($authUserId, $otherUserId) {
            $query->where('sender_id', $otherUserId)->where('receiver_id', $authUserId);
        })->delete();
    }

    /**
     * Toggle favorite status of a message
     */
    public function toggleFavorite(int $messageId, int $userId): Message
    {
        $message = Message::find($messageId);

        if (!$message) {
            throw new BusinessException('Message not found', 404);
        }

        if ($message->sender_id !== $userId && $message->receiver_id !== $userId) {
            throw new BusinessException('Unauthorized', 403);
        }

        $message->is_favorite = !$message->is_favorite;
        $message->save();

        return $message;
    }
}
