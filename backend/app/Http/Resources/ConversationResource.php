<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'is_group' => $this->is_group,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url,
            'created_by' => $this->created_by,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'messages_count' => $this->messages_count ?? 0,
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
