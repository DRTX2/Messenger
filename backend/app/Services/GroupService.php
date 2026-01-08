<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Str;

class GroupService
{
    /**
     * Create a new group conversation.
     */
    public function createGroup(
        int $creatorId,
        string $name,
        array $participantIds,
        ?string $avatarUrl = null,
        ?string $requestId = null
    ): Conversation {
        if ($requestId) {
            $cacheKey = "group_request_{$requestId}";
            $existingGroupId = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($existingGroupId) {
                return Conversation::findOrFail($existingGroupId);
            }
        }

        // Ensure creator is not in the participant list (we'll add them separately as admin)
        $participantIds = array_filter($participantIds, fn($id) => $id !== $creatorId);

        // Create the conversation
        $conversation = Conversation::create([
            'uuid' => Str::uuid()->toString(),
            'is_group' => true,
            'name' => $name,
            'avatar_url' => $avatarUrl,
            'created_by' => $creatorId,
            'last_message_at' => now(),
        ]);

        // Add creator as admin
        $conversation->participants()->attach($creatorId, [
            'is_admin' => true,
            'formatted_last_read_at' => now(),
        ]);

        // Add other participants
        foreach ($participantIds as $participantId) {
            $conversation->participants()->attach($participantId, [
                'is_admin' => false,
                'formatted_last_read_at' => now(),
            ]);
        }

        if ($requestId) {
            \Illuminate\Support\Facades\Cache::put("group_request_{$requestId}", $conversation->id, now()->addHours(24));
        }

        return $conversation;
    }

    /**
     * Add participants to an existing group.
     */
    public function addParticipants(
        Conversation $conversation,
        int $requesterId,
        array $userIds
    ): void {
        $this->ensureIsGroup($conversation);
        \Illuminate\Support\Facades\Gate::authorize('addParticipants', $conversation);

        $existingIds = $conversation->participants()->pluck('users.id')->toArray();

        foreach ($userIds as $userId) {
            if (!in_array($userId, $existingIds)) {
                $conversation->participants()->attach($userId, [
                    'is_admin' => false,
                    'formatted_last_read_at' => now(),
                ]);
            }
        }
    }

    /**
     * Remove a participant from a group.
     */
    public function removeParticipant(
        Conversation $conversation,
        int $requesterId,
        int $userIdToRemove
    ): void {
        $this->ensureIsGroup($conversation);
        \Illuminate\Support\Facades\Gate::authorize('removeParticipant', $conversation);

        if ($requesterId === $userIdToRemove) {
            throw new BusinessException('Use the leave endpoint to remove yourself.', 400);
        }

        $conversation->participants()->detach($userIdToRemove);
    }

    /**
     * Leave a group conversation.
     */
    public function leaveGroup(Conversation $conversation, int $userId): void
    {
        $this->ensureIsGroup($conversation);
        \Illuminate\Support\Facades\Gate::authorize('leave', $conversation);

        $conversation->participants()->detach($userId);

        // If no participants left, soft delete the conversation
        if ($conversation->participants()->count() === 0) {
            $conversation->delete();
        }
    }

    /**
     * Update group details.
     */
    public function updateGroup(
        Conversation $conversation,
        int $requesterId,
        ?string $name = null,
        ?string $avatarUrl = null
    ): Conversation {
        $this->ensureIsGroup($conversation);
        \Illuminate\Support\Facades\Gate::authorize('update', $conversation);

        if ($name !== null) {
            $conversation->name = $name;
        }

        if ($avatarUrl !== null) {
            $conversation->avatar_url = $avatarUrl;
        }

        $conversation->save();

        return $conversation;
    }

    // --- Helpers ---

    private function ensureIsGroup(Conversation $conversation): void
    {
        if (!$conversation->is_group) {
            throw new BusinessException('This operation is only allowed for group conversations.', 400);
        }
    }
}
