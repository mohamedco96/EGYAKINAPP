<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Mailgun\Mailgun;

class MailgunChannel
{
    protected $mailgun;

    public function __construct()
    {
        $this->mailgun = Mailgun::create(
            config('services.mailgun.secret'),
            config('services.mailgun.endpoint', 'https://api.eu.mailgun.net')
        );
    }

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toMailgun')) {
            return $notification->toMailgun($notifiable);
        }
    }
} 