<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Services\PresenceService $presenceService */
        $presenceService = app(\App\Services\PresenceService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_online' => $presenceService->isOnline((int) $this->id),
            'unread_messages' => ((int)auth()->id() === (int)$this->id) 
                ? app(\App\Services\ChatService::class)->getUnreadCount((int)$this->id)
                : 0,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
