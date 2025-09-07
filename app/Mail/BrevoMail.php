<?php

namespace App\Mail;

use App\Services\BrevoApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BrevoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $to;

    public $subject;

    public $htmlContent;

    public $textContent;

    /**
     * Create a new message instance.
     */
    public function __construct($to, $subject, $htmlContent, $textContent = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->htmlContent = $htmlContent;
        $this->textContent = $textContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
            textString: $this->textContent,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Send via Brevo API instead of Laravel's default mailer
     */
    public function sendViaBrevoApi()
    {
        try {
            $brevoService = new BrevoApiService();

            $result = $brevoService->sendEmail(
                $this->to,
                $this->subject,
                $this->htmlContent,
                $this->textContent
            );

            if ($result['success']) {
                Log::info('BrevoMail sent successfully', [
                    'to' => $this->to,
                    'subject' => $this->subject,
                    'message_id' => $result['message_id'],
                ]);

                return $result;
            } else {
                Log::error('BrevoMail failed', [
                    'to' => $this->to,
                    'subject' => $this->subject,
                    'error' => $result['error'],
                ]);

                throw new \Exception('Brevo API Error: '.$result['error']);
            }

        } catch (\Exception $e) {
            Log::error('BrevoMail exception', [
                'to' => $this->to,
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
