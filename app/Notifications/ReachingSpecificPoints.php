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
        //$this->message = 'Use the below code for verification process';
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
        return ['brevo-api'];
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
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        $htmlContent = $this->getHtmlContent($notifiable);
        $textContent = $this->getTextContent($notifiable);

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
    }

    /**
     * Get HTML content for Brevo API
     */
    private function getHtmlContent($notifiable): string
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Congratulations from EGYAKIN</title>
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
                    background: linear-gradient(135deg, #28a745, #20c997);
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                    border-radius: 15px 15px 0 0;
                }
                .content {
                    background-color: #f8f9fa;
                    padding: 40px;
                    border-radius: 0 0 15px 15px;
                }
                .celebration-icon {
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                .score-display {
                    background: linear-gradient(135deg, #ffc107, #ff9800);
                    color: white;
                    padding: 25px;
                    border-radius: 15px;
                    text-align: center;
                    margin: 25px 0;
                    font-size: 24px;
                    font-weight: bold;
                    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
                }
                .achievement-badge {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    border-left: 4px solid #28a745;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="celebration-icon">🎉</div>
                <h1>Congratulations from EGYAKIN!</h1>
                <p>You\'ve reached a new milestone!</p>
            </div>
            
            <div class="content">
                <h2>Hello Doctor '.htmlspecialchars($notifiable->name).'!</h2>
                
                <div class="achievement-badge">
                    <h3>🏆 Achievement Unlocked!</h3>
                    <p>Congratulations! You have earned <strong>50 points</strong> for your outstanding contribution to the EGYAKIN medical community.</p>
                </div>
                
                <div class="score-display">
                    <h3>Your Total Score</h3>
                    <div style="font-size: 36px; margin: 10px 0;">'.$this->score->score.' points</div>
                    <p>Keep up your outstanding work!</p>
                </div>
                
                <p>Your dedication to medical excellence and community engagement is truly commendable. Every point you earn represents your valuable contribution to improving healthcare through EGYAKIN.</p>
                
                <p>Continue sharing your knowledge, helping fellow medical professionals, and making a difference in the medical community!</p>
            </div>
            
            <div class="footer">
                <p>Thank you for using our application!</p>
                <p>Sincerely,<br>
                <strong>EGYAKIN Scientific Team</strong></p>
            </div>
        </body>
        </html>';
    }

    /**
     * Get text content for Brevo API
     */
    private function getTextContent($notifiable): string
    {
        return '
Congratulations from EGYAKIN!

Hello Doctor '.$notifiable->name.'!

ACHIEVEMENT UNLOCKED!
Congratulations! You have earned 50 points for your outstanding contribution to the EGYAKIN medical community.

Your Total Score: '.$this->score->score.' points

Keep up your outstanding work!

Your dedication to medical excellence and community engagement is truly commendable. Every point you earn represents your valuable contribution to improving healthcare through EGYAKIN.

Continue sharing your knowledge, helping fellow medical professionals, and making a difference in the medical community!

Thank you for using our application!

Sincerely,
EGYAKIN Scientific Team
        ';
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
