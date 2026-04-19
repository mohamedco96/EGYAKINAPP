<?php

namespace App\Modules\DirectChat\Services;

use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\Message;
use App\Modules\Notifications\Models\FcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class ChatNotificationService
{
    public function __construct(private readonly FirebaseMessaging $messaging) {}

    /**
     * Send an FCM push notification for a new chat message.
     *
     * Notification rules by conversation type:
     * - private:      both participants have mute_notifications=false → both get FCM
     * - case_group:   all members have mute_notifications=false → all get FCM
     * - social_group: members join with mute_notifications=true  → only unmuted get FCM
     *
     * $recipientIds is already pre-filtered (non-sender + mute_notifications=false)
     * by DirectChatService before dispatching the job.
     */
    public function notifyNewMessage(Message $message, array $recipientIds): void
    {
        if (empty($recipientIds)) {
            return;
        }

        try {
            $message->loadMissing(['sender', 'conversation']);

            $sender = $message->sender;
            $conversation = $message->conversation;

            $title = trim(($sender->name ?? '').' '.($sender->lname ?? ''));
            $body = $this->buildMessagePreview($message);

            // Build data payload so the mobile app can deep-link to the right conversation
            $data = [
                'type' => 'chat_message',
                'conversation_id' => (string) $message->conversation_id,
                'conversation_type' => $conversation?->type ?? 'private',
                'conversation_name' => $conversation?->name ?? '',
                'msg_id' => (string) $message->id,
                'msg_type' => $message->type,
                'sender_id' => (string) $message->sender_id,
                'sender_name' => $title,
            ];

            $tokens = FcmToken::whereIn('doctor_id', $recipientIds)
                ->pluck('token')
                ->toArray();

            if (empty($tokens)) {
                Log::info('DirectChat: No FCM tokens for recipients', [
                    'message_id' => $message->id,
                    'recipient_ids' => $recipientIds,
                ]);

                return;
            }

            $notification = Notification::create($title, $body);
            $messages = [];

            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification)
                    ->withData($data);
            }

            $result = $this->messaging->sendAll($messages);

            $successCount = $result->successes()->count();
            $failureCount = $result->failures()->count();

            // Clean up stale/invalid tokens so they don't accumulate
            if ($failureCount > 0) {
                $invalidTokens = [];

                foreach ($result->failures() as $index => $failure) {
                    $error = $failure->error();
                    $errorCode = method_exists($error, 'getCode') ? $error->getCode() : '';

                    if (in_array($errorCode, ['INVALID_ARGUMENT', 'UNREGISTERED', 'NOT_FOUND'])) {
                        $invalidTokens[] = $tokens[$index] ?? null;
                    }
                }

                $invalidTokens = array_filter($invalidTokens);

                if (! empty($invalidTokens)) {
                    FcmToken::whereIn('token', $invalidTokens)->delete();

                    Log::info('DirectChat: Cleaned up stale FCM tokens', [
                        'message_id' => $message->id,
                        'removed_count' => count($invalidTokens),
                    ]);
                }
            }

            Log::info('DirectChat: Chat push notification sent', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'conversation_type' => $conversation?->type,
                'recipient_count' => count($recipientIds),
                'token_count' => count($tokens),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
            ]);
        } catch (\Throwable $e) {
            Log::error('DirectChat: Failed to send chat push notification', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildMessagePreview(Message $message): string
    {
        return match ($message->type) {
            'image' => '[Image]',
            'voice' => '[Voice Message]',
            'file' => '[File: '.($message->file_metadata['original_name'] ?? 'attachment').']',
            default => mb_strimwidth($message->content ?? '', 0, 100, '...'),
        };
    }
}
