<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\UserTyping;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypingController extends Controller
{
    /**
     * Broadcast that the user is typing in a conversation.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $user = auth()->user();

        // Verify user is a participant
        $isParticipant = $conversation->participants()
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'success' => false,
                'message' => 'Not a participant in this conversation.',
            ], 403);
        }

        $isTyping = $request->boolean('is_typing', true);

        broadcast(new UserTyping(
            conversationId: $conversation->id,
            userId: $user->id,
            userName: $user->name,
            isTyping: $isTyping
        ))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }
}
