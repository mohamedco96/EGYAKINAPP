<?php

namespace App\Mail;

use App\Services\BrevoApiService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class BrevoApiTransport extends AbstractTransport
{
    protected $brevoService;

    public function __construct()
    {
        $this->brevoService = new BrevoApiService();
    }

    /**
     * Send the given Message.
     */
    protected function doSend(SentMessage $message): void
    {
        try {
            $originalMessage = MessageConverter::toEmail($message->getOriginalMessage());

            // Get recipients
            $to = $this->getTo($originalMessage);
            $subject = $originalMessage->getSubject();

            // Get HTML and text content
            $htmlContent = $this->getHtmlContent($originalMessage);
            $textContent = $this->getTextContent($originalMessage);

            // Send via Brevo API
            $result = $this->brevoService->sendEmail(
                $to,
                $subject,
                $htmlContent,
                $textContent
            );

            if ($result['success']) {
                Log::info('Email sent via Brevo API', [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => $result['message_id'],
                ]);
            } else {
                Log::error('Brevo API email failed', [
                    'to' => $to,
                    'subject' => $subject,
                    'error' => $result['error'],
                ]);

                throw new \Exception('Brevo API Error: '.$result['error']);
            }

        } catch (\Exception $e) {
            Log::error('Brevo API transport exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the recipient email address
     */
    protected function getTo($message)
    {
        $to = $message->getTo();

        if (empty($to)) {
            return '';
        }

        // Get first recipient
        $firstTo = $to[0];

        return $firstTo->getAddress();
    }

    /**
     * Get HTML content from the message
     */
    protected function getHtmlContent($message)
    {
        $htmlBody = $message->getHtmlBody();

        if ($htmlBody) {
            return $htmlBody;
        }

        // If no HTML body, convert text to HTML
        $textBody = $message->getTextBody();
        if ($textBody) {
            return '<html><body><pre>'.htmlspecialchars($textBody).'</pre></body></html>';
        }

        return '';
    }

    /**
     * Get text content from the message
     */
    protected function getTextContent($message)
    {
        $textBody = $message->getTextBody();

        if ($textBody) {
            return $textBody;
        }

        // If no text body, strip HTML tags
        $htmlBody = $message->getHtmlBody();
        if ($htmlBody) {
            return strip_tags($htmlBody);
        }

        return '';
    }
}
