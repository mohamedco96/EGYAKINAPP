<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
     * @param array $recipientEmails
     * @param string $message
     */
    public function __construct(array $recipientEmails,string $message)
    {
        $this->subject = 'New Contact Request';
        $this->fromEmail = "noreply@egyakin.com";
        $this->mailer = 'smtp';
        $this->recipientEmails = $recipientEmails;
        $this->mesaage = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     *
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
                    ->greeting('Hello Doctor Mostafa')
                    ->line('Dr.'. $notifiable->name. ' who works at ' .$notifiable->workingplace  .' has raised a new contact request.' )
                    ->line('<< ' . $this->mesaage . ' >>')
                    ->line('He can be reached by Email: '.$notifiable->email.' or Phone: '.$notifiable->phone)
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
