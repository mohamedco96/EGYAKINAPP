<?php

namespace App\Mail;

use App\Services\BrevoApiService;
use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Log;
use Swift_Mime_SimpleMessage;

class BrevoApiTransport extends Transport
{
    protected $brevoService;

    public function __construct()
    {
        $this->brevoService = new BrevoApiService();
    }

    /**
     * Send the given Message.
     *
     * @param  string[]  $failedRecipients  An array of failures by-reference
     * @return int The number of recipients who were accepted for delivery
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        try {
            // Get recipients
            $to = $this->getTo($message);
            $subject = $message->getSubject();

            // Get HTML and text content
            $htmlContent = $this->getHtmlContent($message);
            $textContent = $this->getTextContent($message);

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

                $this->sendPerformed($message);

                return count($message->getTo());
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
    protected function getTo(Swift_Mime_SimpleMessage $message)
    {
        $to = $message->getTo();

        return is_array($to) ? array_keys($to)[0] : $to;
    }

    /**
     * Get HTML content from the message
     */
    protected function getHtmlContent(Swift_Mime_SimpleMessage $message)
    {
        $body = $message->getBody();

        // If it's HTML, return as is
        if ($message->getContentType() === 'text/html') {
            return $body;
        }

        // If it's plain text, convert to HTML
        return '<html><body><pre>'.htmlspecialchars($body).'</pre></body></html>';
    }

    /**
     * Get text content from the message
     */
    protected function getTextContent(Swift_Mime_SimpleMessage $message)
    {
        $body = $message->getBody();

        // If it's plain text, return as is
        if ($message->getContentType() === 'text/plain') {
            return $body;
        }

        // If it's HTML, strip tags for text version
        return strip_tags($body);
    }
}
