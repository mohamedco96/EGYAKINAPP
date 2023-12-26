<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Otp;

class EmailVerificationNotification extends Notification
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
        $this->message = 'Use the below code for verification process';
        $this->subject = 'EGYAKIN Mail Verification';
        $this->fromEmail = "noreply@egyakin.com";
        $this->mailer = 'smtp';
        $this->otp = new Otp;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /*return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');*/

        $otp = $this->otp->generate($notifiable->email,6,60);
        return (new MailMessage)
        ->mailer('smtp')
        ->subject($this->subject)
        ->greeting('Hello ' . $notifiable->name)
        ->line($this->message)
        ->action('Verify', url('/'))
        ->line('Thank you for using our application!')
        ->line('Code: ' . $otp->token);
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
