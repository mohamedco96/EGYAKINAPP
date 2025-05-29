<?php

namespace App\Services;

use Mailgun\Mailgun;

class MailgunService
{
    protected $mg;

    public function __construct()
    {
        $this->mg = Mailgun::create(config('services.mailgun.secret'));
    }

    public function sendVerificationEmail($toEmail, $toName, $verificationUrl)
    {
        $domain = config('services.mailgun.domain');
        $from = config('mail.from.address');
        $name = config('mail.from.name');

        return $this->mg->messages()->send($domain, [
            'from'    => "{$name} <{$from}>",
            'to'      => "{$toName} <{$toEmail}>",
            'subject' => 'Verify Your Email Address',
            'html'    => view('emails.verify', ['url' => $verificationUrl])->render(),
            'text'    => "Please visit this link to verify your email: {$verificationUrl}"
        ]);
    }
}