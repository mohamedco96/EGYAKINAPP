<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoApiService
{
    protected $apiKey;

    protected $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
    }

    /**
     * Send transactional email via Brevo API
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null, $from = null)
    {
        try {
            $from = $from ?? [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ];

            $payload = [
                'sender' => $from,
                'to' => [
                    [
                        'email' => $to,
                        'name' => $to,
                    ],
                ],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ];

            if ($textContent) {
                $payload['textContent'] = $textContent;
            }

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl.'/smtp/email', $payload);

            if ($response->successful()) {
                Log::info('Brevo API email sent successfully', [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => $response->json('messageId'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('messageId'),
                    'response' => $response->json(),
                ];
            } else {
                Log::error('Brevo API email failed', [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Brevo API exception', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send email to multiple recipients via Brevo API
     */
    public function sendEmailToMultipleRecipients($recipients, $subject, $htmlContent, $textContent = null, $from = null)
    {
        try {
            $from = $from ?? [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ];

            // Format recipients for Brevo API
            $toRecipients = [];
            foreach ($recipients as $email) {
                // Ensure email is a string and valid
                $email = trim((string) $email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $toRecipients[] = [
                        'email' => $email,
                        'name' => $email,
                    ];
                }
            }

            // If no valid recipients after formatting, throw error
            if (empty($toRecipients)) {
                throw new \Exception('No valid email recipients provided');
            }

            $payload = [
                'sender' => $from,
                'to' => $toRecipients,
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ];

            if ($textContent) {
                $payload['textContent'] = $textContent;
            }

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl.'/smtp/email', $payload);

            if ($response->successful()) {
                Log::info('Brevo API email sent successfully to multiple recipients', [
                    'recipients' => $recipients,
                    'subject' => $subject,
                    'message_id' => $response->json('messageId'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('messageId'),
                    'response' => $response->json(),
                ];
            } else {
                Log::error('Brevo API email to multiple recipients failed', [
                    'recipients' => $recipients,
                    'subject' => $subject,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Brevo API multiple recipients exception', [
                'recipients' => $recipients,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send template email via Brevo API
     */
    public function sendTemplateEmail($to, $templateId, $params = [], $from = null)
    {
        try {
            $from = $from ?? [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ];

            $payload = [
                'sender' => $from,
                'to' => [
                    [
                        'email' => $to,
                        'name' => $to,
                    ],
                ],
                'templateId' => $templateId,
                'params' => $params,
            ];

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl.'/smtp/email', $payload);

            if ($response->successful()) {
                Log::info('Brevo API template email sent successfully', [
                    'to' => $to,
                    'template_id' => $templateId,
                    'message_id' => $response->json('messageId'),
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json('messageId'),
                    'response' => $response->json(),
                ];
            } else {
                Log::error('Brevo API template email failed', [
                    'to' => $to,
                    'template_id' => $templateId,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            Log::error('Brevo API template exception', [
                'to' => $to,
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get account information
     */
    public function getAccountInfo()
    {
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
            ])->get($this->baseUrl.'/account');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->body(),
                    'status' => $response->status(),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        $accountInfo = $this->getAccountInfo();

        if ($accountInfo['success']) {
            return [
                'success' => true,
                'message' => 'Brevo API connection successful',
                'account' => $accountInfo['data']['email'] ?? 'Unknown',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Brevo API connection failed',
                'error' => $accountInfo['error'],
            ];
        }
    }
}
