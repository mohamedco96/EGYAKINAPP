<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $body;

    /**
     * Create a new message instance.
     */
    public function __construct($subject = null, $body = null)
    {
        $this->subject = $subject ?? 'EGYAKIN Mail Test - '.now()->format('Y-m-d H:i:s');
        $this->body = $body ?? $this->getDefaultBody();
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
            view: 'emails.test',
            with: [
                'subject' => $this->subject,
                'body' => $this->body,
            ]
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
     * Get default email body
     */
    private function getDefaultBody()
    {
        return 'This is a test email from EGYAKIN application to verify mail configuration.';
    }
}
