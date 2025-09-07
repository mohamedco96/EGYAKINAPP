<?php

namespace App\Notifications\Channels;

use App\Services\BrevoApiService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class BrevoApiChannel
{
    protected $brevoService;

    public function __construct()
    {
        $this->brevoService = new BrevoApiService();
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        try {
            // Get the message data from the notification
            $message = $notification->toBrevoApi($notifiable);

            if (! $message) {
                Log::warning('No Brevo API message data provided', [
                    'notification' => get_class($notification),
                    'notifiable' => get_class($notifiable),
                ]);

                return;
            }

            // Send via Brevo API
            $result = $this->brevoService->sendEmail(
                $message['to'],
                $message['subject'],
                $message['htmlContent'],
                $message['textContent'] ?? null,
                $message['from'] ?? null
            );

            if ($result['success']) {
                Log::info('Brevo API notification sent successfully', [
                    'to' => $message['to'],
                    'subject' => $message['subject'],
                    'message_id' => $result['message_id'],
                    'notification' => get_class($notification),
                ]);
            } else {
                Log::error('Brevo API notification failed', [
                    'to' => $message['to'],
                    'subject' => $message['subject'],
                    'error' => $result['error'],
                    'notification' => get_class($notification),
                ]);

                throw new \Exception('Brevo API Error: '.$result['error']);
            }

        } catch (\Exception $e) {
            Log::error('Brevo API channel exception', [
                'notification' => get_class($notification),
                'notifiable' => get_class($notifiable),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
