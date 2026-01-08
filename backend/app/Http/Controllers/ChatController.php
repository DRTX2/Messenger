<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
    }

    /**
     * Get list of users for chat
     */
    public function users(Request $request): JsonResource
    {
        $perPage = (int) $request->input('per_page', 10);
        
        $users = $this->chatService->getUsers(
            (int) auth()->id(),
            min($perPage, 50),
            $request->input('search')
        );

        return UserResource::collection($users)->additional(['success' => true]);
    }

    /**
     * Get conversation messages with a specific user
     */
    public function messages(Request $request, User $user): JsonResource
    {
        $perPage = (int) $request->input('per_page', 20);

        $messages = $this->chatService->getConversation(
            (int) auth()->id(),
            $user->id,
            min($perPage, 50)
        );

        $this->chatService->markAsRead((int) auth()->id(), $user->id);

        return MessageResource::collection($messages)->additional(['success' => true]);
    }

    /**
     * Send a message to a user
     */
    public function send(SendMessageRequest $request, User $user): JsonResponse
    {
        $message = $this->chatService->sendMessage(
            (int) auth()->id(),
            $user->id,
            (string) $request->validated('message', ''),
            $request->validated('attachment_ids', []),
            (string) $request->validated('request_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => new MessageResource($message)
        ], 201);
    }

    /**
     * Get unread message count for authenticated user
     */
    public function unreadCount(): JsonResponse
    {
        $count = $this->chatService->getUnreadCount((int) auth()->id());

        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /**
     * Clear conversation metadata/messages
     */
    public function clear(Request $request, User $user): JsonResponse
    {
        $this->chatService->clearConversation((int) auth()->id(), $user->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Conversation cleared'
        ]);
    }

    /**
     * Toggle favorite
     */
    public function toggleFavorite(Request $request, Message $message): JsonResponse
    {
        $this->authorize('favorite', $message);

        $updatedMessage = $this->chatService->toggleFavorite($message->id, (int) auth()->id());
        
        return response()->json([
            'success' => true,
            'message' => 'Favorite toggled',
            'data' => new MessageResource($updatedMessage)
        ]);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Message $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->chatService->deleteMessage($message->id, (int) auth()->id());
            
        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
}
