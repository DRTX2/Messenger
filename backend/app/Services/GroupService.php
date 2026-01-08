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
        ?string $avatarUrl = null
    ): Conversation {
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
        $this->ensureIsAdmin($conversation, $requesterId);

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
        $this->ensureIsAdmin($conversation, $requesterId);

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
        $this->ensureIsParticipant($conversation, $userId);

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
        $this->ensureIsAdmin($conversation, $requesterId);

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

    private function ensureIsAdmin(Conversation $conversation, int $userId): void
    {
        $participant = $conversation->participants()->where('user_id', $userId)->first();

        if (!$participant || !$participant->pivot->is_admin) {
            throw new BusinessException('Only group admins can perform this action.', 403);
        }
    }

    private function ensureIsParticipant(Conversation $conversation, int $userId): void
    {
        $isParticipant = $conversation->participants()->where('user_id', $userId)->exists();

        if (!$isParticipant) {
            throw new BusinessException('You are not a participant in this group.', 403);
        }
    }
}
