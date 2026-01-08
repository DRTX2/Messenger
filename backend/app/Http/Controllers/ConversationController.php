<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationController extends Controller
{
    public function __construct(protected ChatService $chatService)
    {
    }

    /**
     * Get list of my conversations (Inbox)
     */
    public function index(Request $request): JsonResource
    {
        $perPage = (int) $request->input('per_page', 20);
        $conversations = $this->chatService->getConversations((int) auth()->id(), $perPage);
        
        return ConversationResource::collection($conversations);
    }
}
