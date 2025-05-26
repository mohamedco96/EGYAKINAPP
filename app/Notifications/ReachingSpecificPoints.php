<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReachingSpecificPoints extends Notification
{
    use Queueable;

    public $mesaage;

    public $subject;

    public $fromEmail;

    public $mailer;

    protected $score;

    /**
     * Create a new notification instance.
     */
    public function __construct($score)
    {
        // $this->message = 'Use the below code for verification process';
        $this->subject = 'Congrats from EGYAKIN';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'smtp';
        $this->score = $score;
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
            ->line('Congrats! You have earned 50 points.')
            ->line('Your score is '.$this->score->score.' points overall. Keep up your outstanding work.')
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
