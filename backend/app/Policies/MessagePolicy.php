<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Determine whether the user can update the message.
     */
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id;
    }

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(User $user, Message $message): bool
    {
        // One can delete their own message.
        // In a group, maybe an admin can delete any message? 
        if ($user->id === $message->sender_id) {
            return true;
        }

        // Logic for group admin deleting messages
        if ($message->conversation && $message->conversation->is_group) {
            return $message->conversation->participants()
                ->where('user_id', $user->id)
                ->where('is_admin', true)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can toggle favorite on the message.
     */
    public function favorite(User $user, Message $message): bool
    {
        // This is usually per user, but here it's a column in the messages table (global for the message?)
        // If it's global, maybe only participants can do it.
        // If it's per-user favorite, we'd need a pivot table. 
        // Based on current model, it's a boolean in messages table.
        return $message->conversation->participants()->where('user_id', $user->id)->exists();
    }
}
