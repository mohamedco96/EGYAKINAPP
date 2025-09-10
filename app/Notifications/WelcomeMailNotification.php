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
        //$this->message = 'Use the below code for verification process';
        $this->subject = 'Greetings from EGYAKIN';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
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
            ->subject($this->subject)
            ->greeting('Welcome to EGYAKIN!')
            ->line('Thank you for joining our medical community.')
            ->line('We\'re excited to have you on board!')
            ->salutation('Best regards, EGYAKIN Team');
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
            <title>Welcome to EGYAKIN</title>
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
                .welcome-icon {
                    font-size: 60px;
                    margin-bottom: 20px;
                }
                .features {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    margin: 30px 0;
                }
                .feature {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .feature-icon {
                    font-size: 30px;
                    margin-bottom: 10px;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
                .cta-button {
                    background: linear-gradient(135deg, #007bff, #0056b3);
                    color: white;
                    padding: 15px 30px;
                    text-decoration: none;
                    border-radius: 25px;
                    display: inline-block;
                    margin: 20px 0;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="welcome-icon">🎉</div>
                <h1>Welcome to EGYAKIN!</h1>
                <p>Your Medical Practice Revolution Starts Here</p>
            </div>
            
            <div class="content">
                <h2>Hello '.htmlspecialchars($notifiable->name).'!</h2>
                
                <p>Welcome to EGYAKIN! We\'re thrilled to have you join our growing community of medical professionals.</p>
                
                <p>EGYAKIN is designed to streamline your medical practice and enhance patient care through innovative technology.</p>
                
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">👥</div>
                        <h3>Patient Management</h3>
                        <p>Organize and track patient information efficiently</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">💬</div>
                        <h3>Consultations</h3>
                        <p>Connect with colleagues and share medical insights</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">📊</div>
                        <h3>Analytics</h3>
                        <p>Track your practice performance and growth</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🔒</div>
                        <h3>Security</h3>
                        <p>HIPAA-compliant data protection and privacy</p>
                    </div>
                </div>
                
                <p>Ready to get started? Explore all the features EGYAKIN has to offer!</p>
                
                <div style="text-align: center;">
                    <a href="https://test.egyakin.com" class="cta-button">Start Your Journey</a>
                </div>
                
                <p>If you have any questions or need assistance, our support team is here to help.</p>
            </div>
            
            <div class="footer">
                <p>Best regards,<br>
                <strong>EGYAKIN Development Team</strong></p>
                
                <p><small>Thank you for choosing EGYAKIN for your medical practice!</small></p>
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
Welcome to EGYAKIN!

Hello '.$notifiable->name.'!

Welcome to EGYAKIN! We\'re thrilled to have you join our growing community of medical professionals.

EGYAKIN is designed to streamline your medical practice and enhance patient care through innovative technology.

Key Features:
- Patient Management: Organize and track patient information efficiently
- Consultations: Connect with colleagues and share medical insights  
- Analytics: Track your practice performance and growth
- Security: HIPAA-compliant data protection and privacy

Ready to get started? Visit https://test.egyakin.com to explore all the features EGYAKIN has to offer!

If you have any questions or need assistance, our support team is here to help.

Best regards,
EGYAKIN Development Team

Thank you for choosing EGYAKIN for your medical practice!
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
