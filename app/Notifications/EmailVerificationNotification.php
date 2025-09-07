<?php

namespace App\Notifications;

use App\Notifications\Channels\MailgunChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Otp;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    // test
    use Queueable;

    public $message;

    public $subject;

    public $fromEmail;

    public $domain;

    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        try {
            $this->message = 'Use the below code for verification process';
            $this->subject = 'EGYAKIN Mail Verification';
            $this->fromEmail = config('mail.from.address');
            $this->domain = config('services.mailgun.domain', 'egyakin.com');
            $this->otp = new Otp;

            Log::info('EmailVerificationNotification initialized:', [
                'fromEmail' => $this->fromEmail,
                'domain' => $this->domain,
                'mailgun_domain_config' => config('services.mailgun.domain'),
                'mail_from_config' => config('mail.from.address'),
            ]);
        } catch (\Exception $e) {
            Log::error('EmailVerificationNotification Construction Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['brevo-api'];
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        try {
            $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);

            $htmlContent = $this->getHtmlContent($notifiable, $otp->token);
            $textContent = $this->getTextContent($notifiable, $otp->token);

            Log::info('Preparing to send verification email via Brevo API:', [
                'email' => $notifiable->email,
                'otp_token' => $otp->token,
            ]);

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
        } catch (\Exception $e) {
            Log::error('Error preparing Brevo API verification email:', [
                'email' => $notifiable->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
            <title>EGYAKIN Email Verification</title>
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
                    background: linear-gradient(135deg, #007bff, #0056b3);
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
                .otp-code {
                    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
                    padding: 20px;
                    border-radius: 10px;
                    margin: 25px 0;
                    text-align: center;
                    font-size: 32px;
                    font-weight: bold;
                    color: #1976d2;
                    border: 2px solid #2196f3;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
                .security-note {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📧 EGYAKIN Email Verification</h1>
                <p>Welcome to the EGYAKIN Medical Community</p>
            </div>
            
            <div class="content">
                <h2>Hello '.htmlspecialchars($notifiable->name).'!</h2>
                
                <p>Welcome to EGYAKIN! We\'re excited to have you join our medical community.</p>
                
                <p>'.htmlspecialchars($this->message).'</p>
                
                <div class="otp-code">
                    '.$otpToken.'
                </div>
                
                <div class="security-note">
                    <strong>🔒 Security Note:</strong> This verification code will expire in 10 minutes. Please use it promptly to verify your email address.
                </div>
                
                <p>Once verified, you\'ll have full access to:</p>
                <ul>
                    <li>📊 Patient management tools</li>
                    <li>💬 Medical consultations</li>
                    <li>📝 Clinical documentation</li>
                    <li>👥 Professional networking</li>
                </ul>
                
                <p>Thank you for choosing EGYAKIN for your medical practice!</p>
            </div>
            
            <div class="footer">
                <p>Best regards,<br>
                <strong>EGYAKIN Development Team</strong></p>
                
                <p><small>If you did not create an account with EGYAKIN, please ignore this email.</small></p>
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
Welcome to EGYAKIN!

Hello '.$notifiable->name.'!

Welcome to EGYAKIN! We\'re excited to have you join our medical community.

'.$this->message.'

Your verification code is: '.$otpToken.'

This verification code will expire in 10 minutes. Please use it promptly to verify your email address.

Once verified, you\'ll have full access to:
- Patient management tools
- Medical consultations  
- Clinical documentation
- Professional networking

Thank you for choosing EGYAKIN for your medical practice!

Best regards,
EGYAKIN Development Team

If you did not create an account with EGYAKIN, please ignore this email.
        ';
    }

    /**
     * Send the notification using Mailgun API (Legacy method - kept for compatibility).
     */
    public function toMailgun(object $notifiable)
    {
        try {
            Log::info('Preparing to send verification email:', [
                'notifiable_email' => $notifiable->email,
                'notifiable_name' => $notifiable->name,
                'domain' => $this->domain,
            ]);

            $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);
            Log::info('OTP generated successfully', ['email' => $notifiable->email]);

            $emailContent = "Hello {$notifiable->name},\n\n";
            $emailContent .= "{$this->message}\n";
            $emailContent .= "Your verification code: {$otp->token}\n";
            $emailContent .= "This code will expire in 10 minutes\n";
            $emailContent .= 'If you did not request this, please ignore this email.';

            Log::info('Attempting to send email via Mailgun', [
                'domain' => $this->domain,
                'from' => "EGYAKIN <{$this->fromEmail}>",
                'to' => "{$notifiable->name} <{$notifiable->email}>",
            ]);

            $result = app(MailgunChannel::class)->mailgun->messages()->send($this->domain, [
                'from' => "EGYAKIN <{$this->fromEmail}>",
                'to' => "{$notifiable->name} <{$notifiable->email}>",
                'subject' => $this->subject,
                'text' => $emailContent,
            ]);

            Log::info('Email sent successfully via Mailgun', ['result' => $result]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send verification email:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notifiable_email' => $notifiable->email,
            ]);
            throw $e;
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
