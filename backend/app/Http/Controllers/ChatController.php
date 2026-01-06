<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        $this->middleware('auth:api');
    }

    /**
     * Get list of users for chat
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10);
            
            $users = $this->chatService->getUsers(
                auth('api')->id(),
                min($perPage, 50) // Cap at 50 per page for security
            );

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation messages with a specific user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function messages(Request $request, User $user): JsonResponse
    {
        try {
            $authUserId = auth('api')->id();
            $perPage = $request->query('per_page', 20);

            $messages = $this->chatService->getConversation(
                $authUserId,
                $user->id,
                min($perPage, 50)
            );

            // Mark messages as read
            $this->chatService->markAsRead($authUserId, $user->id);

            return response()->json([
                'success' => true,
                'data' => $messages->items(),
                'pagination' => [
                    'total' => $messages->total(),
                    'per_page' => $messages->perPage(),
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Send a message to a user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function send(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => ['required', 'string', 'min:1', 'max:5000'],
            ]);

            $message = $this->chatService->sendMessage(
                auth('api')->id(),
                $user->id,
                trim($validated['message'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Get unread message count for authenticated user
     *
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $count = $this->chatService->getUnreadCount(auth('api')->id());

            return response()->json([
                'success' => true,
                'unread_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a message
     *
     * @param Message $message
     * @return JsonResponse
     */
    public function deleteMessage(Message $message): JsonResponse
    {
        try {
            $this->chatService->deleteMessage($message->id, auth('api')->id());

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
}
