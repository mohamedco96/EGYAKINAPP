<?php

namespace App\Modules\DirectChat\Events;

use App\Modules\DirectChat\Models\Message;
use App\Modules\DirectChat\Resources\MessageResource;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => (new MessageResource($this->message))->resolve(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
