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
                'mail_from_config' => config('mail.from.address')
            ]);
        } catch (\Exception $e) {
            Log::error('EmailVerificationNotification Construction Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [MailgunChannel::class];
    }

    /**
     * Send the notification using Mailgun API.
     */
    public function toMailgun(object $notifiable)
    {
        try {
            Log::info('Preparing to send verification email:', [
                'notifiable_email' => $notifiable->email,
                'notifiable_name' => $notifiable->name,
                'domain' => $this->domain
            ]);

            $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);
            Log::info('OTP generated successfully', ['email' => $notifiable->email]);
            
            $emailContent = "Hello {$notifiable->name},\n\n";
            $emailContent .= "{$this->message}\n";
            $emailContent .= "Your verification code: {$otp->token}\n";
            $emailContent .= "This code will expire in 10 minutes\n";
            $emailContent .= "If you did not request this, please ignore this email.";

            Log::info('Attempting to send email via Mailgun', [
                'domain' => $this->domain,
                'from' => "EGYAKIN <{$this->fromEmail}>",
                'to' => "{$notifiable->name} <{$notifiable->email}>"
            ]);

            $result = app(MailgunChannel::class)->mailgun->messages()->send($this->domain, [
                'from'    => "EGYAKIN <{$this->fromEmail}>",
                'to'      => "{$notifiable->name} <{$notifiable->email}>",
                'subject' => $this->subject,
                'text'    => $emailContent
            ]);

            Log::info('Email sent successfully via Mailgun', ['result' => $result]);
            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send verification email:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notifiable_email' => $notifiable->email
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