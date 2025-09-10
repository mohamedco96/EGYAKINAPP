<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactRequestNotification extends Notification
{
    use Queueable;

    public $mesaage;

    public $subject;

    public $fromEmail;

    public $mailer;

    protected $recipientEmails;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $recipientEmails, string $message)
    {
        $this->subject = 'New Contact Request';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
        $this->recipientEmails = $recipientEmails;
        $this->mesaage = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['brevo-api'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->mailer('brevo-api')
            ->subject($this->subject)
            ->greeting('Hello Doctor Mostafa')
            ->line('Dr.'.$notifiable->name.' who works at '.$notifiable->workingplace.' has raised a new contact request.')
            ->line('<< '.$this->mesaage.' >>')
            ->line('He can be reached by Email: '.$notifiable->email.' or Phone: '.$notifiable->phone)
            ->line('Sincerely,')
            ->salutation('EGYAKIN Scientific Team.');
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        $htmlContent = $this->getHtmlContent($notifiable);
        $textContent = $this->getTextContent($notifiable);

        return [
            'to' => $notifiable->email,
            'subject' => $this->subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent,
            'from' => [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ],
        ];
    }

    /**
     * Get HTML content for Brevo API
     */
    private function getHtmlContent($notifiable): string
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>New Contact Request</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #17a2b8, #138496);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                    border-radius: 12px 12px 0 0;
                }
                .content {
                    background-color: #f8f9fa;
                    padding: 30px;
                    border-radius: 0 0 12px 12px;
                }
                .contact-info {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    border-left: 4px solid #17a2b8;
                }
                .message-box {
                    background-color: #e9ecef;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                    font-style: italic;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📞 New Contact Request</h1>
                <p>EGYAKIN Medical Community</p>
            </div>
            
            <div class="content">
                <h2>Hello Doctor Mostafa!</h2>
                
                <p>Dr. '.htmlspecialchars($notifiable->name).' who works at '.htmlspecialchars($notifiable->workingplace).' has raised a new contact request.</p>
                
                <div class="message-box">
                    <strong>Message:</strong><br>
                    "'.htmlspecialchars($this->mesaage).'"
                </div>
                
                <div class="contact-info">
                    <h3>📋 Contact Information</h3>
                    <p><strong>Name:</strong> Dr. '.htmlspecialchars($notifiable->name).'</p>
                    <p><strong>Workplace:</strong> '.htmlspecialchars($notifiable->workingplace).'</p>
                    <p><strong>Email:</strong> '.htmlspecialchars($notifiable->email).'</p>
                    <p><strong>Phone:</strong> '.htmlspecialchars($notifiable->phone).'</p>
                </div>
                
                <p>Please reach out to them using the contact information provided above.</p>
            </div>
            
            <div class="footer">
                <p>Sincerely,<br>
                <strong>EGYAKIN Scientific Team</strong></p>
            </div>
        </body>
        </html>';
    }

    /**
     * Get text content for Brevo API
     */
    private function getTextContent($notifiable): string
    {
        return '
New Contact Request

Hello Doctor Mostafa!

Dr. '.$notifiable->name.' who works at '.$notifiable->workingplace.' has raised a new contact request.

Message: "'.$this->mesaage.'"

Contact Information:
- Name: Dr. '.$notifiable->name.'
- Workplace: '.$notifiable->workingplace.'
- Email: '.$notifiable->email.'
- Phone: '.$notifiable->phone.'

Please reach out to them using the contact information provided above.

Sincerely,
EGYAKIN Scientific Team
        ';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
