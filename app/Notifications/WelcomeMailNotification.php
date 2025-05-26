<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMailNotification extends Notification
{
    use Queueable;

    public $mesaage;

    public $subject;

    public $fromEmail;

    public $mailer;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        // $this->message = 'Use the below code for verification process';
        $this->subject = 'Greetings from EGYAKIN';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'smtp';
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

        return (new MailMessage)
            ->mailer('smtp')
            ->subject($this->subject)
            ->greeting('Hello Doctor '.$notifiable->name)
            ->line('We are delighted to welcome you to our registration system for kidney injury patients. This system has been created to assist you in managing your patients care more efficiently and effectively.')
            ->line('Through this system, you will be able to enroll new patients, update their information, and monitor their progress. Moreover, you will have real-time access to crucial patient data, including lab results and medical histories.')
            ->line('Recognizing the value of your time, we believe this system will help you save time and enhance patient outcomes. Should you have any inquiries or concerns, please feel free to reach out to us.')
            ->line('We appreciate your dedication to offering the highest quality care to your patients.')
            ->line('Thank you for using our application!')
            ->line('Sincerely,')
            ->salutation('EGYAKIN Scientific Team.');
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
