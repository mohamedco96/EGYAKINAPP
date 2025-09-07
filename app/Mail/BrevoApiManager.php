<?php

namespace App\Mail;

use App\Services\BrevoApiService;
use Illuminate\Support\Facades\Log;

class BrevoApiManager
{
    protected $brevoService;

    public function __construct()
    {
        $this->brevoService = new BrevoApiService();
    }

    /**
     * Send email via Brevo API
     */
    public function send($to, $subject, $htmlContent, $textContent = null, $from = null)
    {
        try {
            $from = $from ?? [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ];

            $result = $this->brevoService->sendEmail(
                $to,
                $subject,
                $htmlContent,
                $textContent,
                $from
            );

            if ($result['success']) {
                Log::info('Email sent via Brevo API Manager', [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => $result['message_id'],
                ]);

                return $result;
            } else {
                Log::error('Brevo API Manager email failed', [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $result['error'],
                ]);

                throw new \Exception('Brevo API Error: '.$result['error']);
            }

        } catch (\Exception $e) {
            Log::error('Brevo API Manager exception', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
