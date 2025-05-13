<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Mailgun\Mailgun;
use Illuminate\Support\Facades\Log;

class MailgunChannel
{
    protected $mailgun;

    public function __construct()
    {
        try {
            $secret = config('services.mailgun.secret');
            $endpoint = config('services.mailgun.endpoint');
            
            Log::info('Mailgun Configuration:', [
                'secret_exists' => !empty($secret),
                'endpoint' => $endpoint,
                'raw_endpoint' => $endpoint
            ]);

            if (empty($endpoint)) {
                $endpoint = 'api.eu.mailgun.net';
                Log::info('Using default endpoint: ' . $endpoint);
            }
            
            // Remove 'https://' if present as Mailgun SDK expects just the host
            $endpoint = str_replace('https://', '', $endpoint);
            Log::info('Processed endpoint: ' . $endpoint);
            
            // Create the Mailgun client with both secret and endpoint
            $this->mailgun = Mailgun::create($secret, $endpoint);
            
            Log::info('Mailgun client created successfully');
        } catch (\Exception $e) {
            Log::error('Mailgun Channel Construction Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function send($notifiable, Notification $notification)
    {
        try {
            Log::info('Attempting to send Mailgun notification', [
                'notifiable_email' => $notifiable->email ?? 'unknown',
                'notification_type' => get_class($notification)
            ]);

            if (method_exists($notification, 'toMailgun')) {
                $result = $notification->toMailgun($notifiable);
                Log::info('Mailgun notification sent successfully', [
                    'result' => $result
                ]);
                return $result;
            }

            Log::warning('toMailgun method not found in notification class');
            return null;
        } catch (\Exception $e) {
            Log::error('Mailgun Send Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 