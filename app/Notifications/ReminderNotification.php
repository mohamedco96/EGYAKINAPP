<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public $mesaage;

    public $subject;

    public $fromEmail;

    public $mailer;

    protected $patient;

    protected $events;

    /**
     * Create a new notification instance.
     */
    public function __construct($patient, $events)
    {
        // $this->message = 'Use the below code for verification process';
        $this->subject = 'Reminder from EGYAKIN';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'smtp';
        $this->patient = $patient;
        $this->events = $events;

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
            ->line('The Patient "'.$this->patient->name.'" outcome has not yet been submitted, please update it right now.')
            ->line('Your Patient was added since '.$this->events->created_at)
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
