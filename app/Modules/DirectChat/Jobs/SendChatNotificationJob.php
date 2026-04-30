<?php

namespace App\Modules\DirectChat\Jobs;

use App\Modules\DirectChat\Models\Message;
use App\Modules\DirectChat\Services\ChatNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly Message $message,
        public readonly array $recipientIds,
    ) {}

    public function handle(ChatNotificationService $notificationService): void
    {
        $notificationService->notifyNewMessage($this->message, $this->recipientIds);
    }
}
