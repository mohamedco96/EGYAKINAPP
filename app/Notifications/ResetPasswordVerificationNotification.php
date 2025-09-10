<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Otp;

class ResetPasswordVerificationNotification extends Notification
{
    use Queueable;

    public $mesaage;

    public $subject;

    public $fromEmail;

    public $mailer;

    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->message = 'Use the below code for resetting your password';
        $this->subject = 'EGYAKIN Reset Mail Password';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
        $this->otp = new Otp;

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
        $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);

        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name)
            ->line($this->message)
        //->action('Verify', url('/'))
            ->line('Thank you for using our application!')
            ->line('Code: '.$otp->token);
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);

        $htmlContent = $this->getHtmlContent($notifiable, $otp->token);
        $textContent = $this->getTextContent($notifiable, $otp->token);

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
    private function getHtmlContent($notifiable, $otpToken): string
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>EGYAKIN Password Reset</title>
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
                    background-color: #007bff;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 8px 8px 0 0;
                }
                .content {
                    background-color: #f8f9fa;
                    padding: 20px;
                    border-radius: 0 0 8px 8px;
                }
                .otp-code {
                    background-color: #e3f2fd;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 15px 0;
                    text-align: center;
                    font-size: 24px;
                    font-weight: bold;
                    color: #1976d2;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>🔐 EGYAKIN Password Reset</h1>
            </div>
            
            <div class="content">
                <h2>Hello '.htmlspecialchars($notifiable->name).'!</h2>
                
                <p>'.htmlspecialchars($this->message).'</p>
                
                <div class="otp-code">
                    '.$otpToken.'
                </div>
                
                <p>Use this code to reset your password. This code will expire in 10 minutes.</p>
                
                <p>Thank you for using our application!</p>
            </div>
            
            <div class="footer">
                <p>Best regards,<br>
                <strong>EGYAKIN Team</strong></p>
                
                <p><small>If you did not request this password reset, please ignore this email.</small></p>
            </div>
        </body>
        </html>';
    }

    /**
     * Get text content for Brevo API
     */
    private function getTextContent($notifiable, $otpToken): string
    {
        return '
Hello '.$notifiable->name.'!

'.$this->message.'

Your reset code is: '.$otpToken.'

Use this code to reset your password. This code will expire in 10 minutes.

Thank you for using our application!

Best regards,
EGYAKIN Team

If you did not request this password reset, please ignore this email.
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
