<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Mailgun\Mailgun;
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
    protected $mailgun;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->message = 'Use the below code for verification process';
        $this->subject = 'EGYAKIN Mail Verification';
        $this->fromEmail = config('mail.from.address');
        $this->domain = 'egyakin.com';
        $this->otp = new Otp;
        
        // Initialize Mailgun client
        $this->mailgun = Mailgun::create(
            config('services.mailgun.secret'),
            'https://api.eu.mailgun.net'
        );
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mailgun'];
    }

    /**
     * Send the notification using Mailgun API.
     */
    public function toMailgun(object $notifiable)
    {
        $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);
        
        $emailContent = "Hello {$notifiable->name},\n\n";
        $emailContent .= "{$this->message}\n";
        $emailContent .= "Your verification code: {$otp->token}\n";
        $emailContent .= "This code will expire in 10 minutes\n";
        $emailContent .= "If you did not request this, please ignore this email.";

        return $this->mailgun->messages()->send($this->domain, [
            'from'    => "EGYAKIN <{$this->fromEmail}>",
            'to'      => "{$notifiable->name} <{$notifiable->email}>",
            'subject' => $this->subject,
            'text'    => $emailContent
        ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}