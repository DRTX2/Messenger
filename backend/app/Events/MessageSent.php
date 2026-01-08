<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        // We broadcast the resource to ensure the frontend receives the exact same structure as the API
        $this->message = (new MessageResource($message))->resolve();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to the specific conversation channel
        // Channel name: conversation.{id}
        // Ensure frontend subscribes to `private-conversation.{id}`
        $conversationId = $this->message['conversation_id'] ?? null;
        
        if (!$conversationId) {
             // Fallback for legacy DMs if any (though we pivoted)
            return [
                new PrivateChannel('user.' . $this->message['receiver_id']),
                new PrivateChannel('user.' . $this->message['sender_id']),
            ];
        }

        return [
            new PrivateChannel('conversation.' . $conversationId),
        ];
    }
}
