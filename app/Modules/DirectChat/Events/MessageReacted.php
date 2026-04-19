<?php

namespace App\Modules\DirectChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $messageId,
        public readonly int $conversationId,
        public readonly int $userId,
        public readonly string $userName,
        public readonly string $reaction,
        public readonly string $action, // 'added' or 'removed'
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
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'reaction' => $this->reaction,
            'action' => $this->action,
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reacted';
    }
}
