<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateGroupRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        private readonly GroupService $groupService
    ) {}

    /**
     * Create a new group conversation.
     */
    public function store(CreateGroupRequest $request): JsonResponse
    {
        $user = auth()->user();

        $conversation = $this->groupService->createGroup(
            creatorId: $user->id,
            name: $request->validated('name'),
            participantIds: $request->validated('participant_ids'),
            avatarUrl: $request->validated('avatar_url')
        );

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully.',
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage'])),
        ], 201);
    }

    /**
     * Add participants to an existing group.
     */
    public function addParticipants(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = auth()->user();

        $this->groupService->addParticipants(
            conversation: $conversation,
            requesterId: $user->id,
            userIds: $request->input('user_ids')
        );

        return response()->json([
            'success' => true,
            'message' => 'Participants added successfully.',
            'data' => new ConversationResource($conversation->fresh(['participants', 'latestMessage'])),
        ]);
    }

    /**
     * Remove a participant from a group (admin only).
     */
    public function removeParticipant(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        $user = auth()->user();

        $this->groupService->removeParticipant(
            conversation: $conversation,
            requesterId: $user->id,
            userIdToRemove: $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Participant removed.',
        ]);
    }

    /**
     * Leave a group.
     */
    public function leave(Conversation $conversation): JsonResponse
    {
        $user = auth()->user();

        $this->groupService->leaveGroup(
            conversation: $conversation,
            userId: $user->id
        );

        return response()->json([
            'success' => true,
            'message' => 'You have left the group.',
        ]);
    }

    /**
     * Update group details (name, avatar).
     */
    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'avatar_url' => ['sometimes', 'nullable', 'url', 'max:500'],
        ]);

        $user = auth()->user();

        $this->groupService->updateGroup(
            conversation: $conversation,
            requesterId: $user->id,
            name: $request->input('name'),
            avatarUrl: $request->input('avatar_url')
        );

        return response()->json([
            'success' => true,
            'message' => 'Group updated.',
            'data' => new ConversationResource($conversation->fresh(['participants', 'latestMessage'])),
        ]);
    }
}
