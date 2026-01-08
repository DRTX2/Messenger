<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send messages in the conversation.
     */
    public function message(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the conversation (e.g. rename group).
     */
    public function update(User $user, Conversation $conversation): bool
    {
        // Only participants can update, maybe only admins for group?
        if (!$conversation->is_group) {
            return $conversation->participants()->where('user_id', $user->id)->exists();
        }

        return $conversation->participants()
            ->where('user_id', $user->id)
            ->where('is_admin', true)
            ->exists();
    }

    /**
     * Determine whether the user can clear the conversation messages.
     */
    public function clear(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can add participants to the group.
     */
    public function addParticipants(User $user, Conversation $conversation): bool
    {
        return $conversation->is_group && 
               $conversation->participants()
                   ->where('user_id', $user->id)
                   ->where('is_admin', true)
                   ->exists();
    }

    /**
     * Determine whether the user can remove participants from the group.
     */
    public function removeParticipant(User $user, Conversation $conversation): bool
    {
        return $this->addParticipants($user, $conversation);
    }

    /**
     * Determine whether the user can leave the group.
     */
    public function leave(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }
}
