<?php

namespace App\Modules\DirectChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $messageId,
        public readonly int $conversationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.'.$this->conversationId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }
}
