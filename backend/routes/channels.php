<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in this conversation
    return \App\Models\Conversation::find($conversationId)
        ?->participants()
        ->where('user_id', $user->id)
        ->exists() ?? false;
});
