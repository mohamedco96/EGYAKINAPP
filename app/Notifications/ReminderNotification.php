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
        //$this->message = 'Use the below code for verification process';
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
        return ['brevo-api'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello Doctor '.$notifiable->name)
            ->line('The Patient "'.$this->patient->name.'" outcome has not yet been submitted, please update it right now.')
            ->line('Your Patient was added since '.$this->events->created_at)
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
            <title>Patient Outcome Reminder</title>
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
                    background: linear-gradient(135deg, #ffc107, #ff9800);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                    border-radius: 12px 12px 0 0;
                }
                .content {
                    background-color: #f8f9fa;
                    padding: 30px;
                    border-radius: 0 0 12px 12px;
                }
                .reminder-icon {
                    font-size: 50px;
                    margin-bottom: 15px;
                }
                .patient-info {
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    margin: 20px 0;
                    border-left: 4px solid #ffc107;
                }
                .urgent-notice {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #666;
                    font-size: 14px;
                }
                .cta-button {
                    background: linear-gradient(135deg, #dc3545, #c82333);
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
                <div class="reminder-icon">⏰</div>
                <h1>Patient Outcome Reminder</h1>
                <p>Action Required - Please Update Patient Status</p>
            </div>
            
            <div class="content">
                <h2>Hello Doctor '.htmlspecialchars($notifiable->name).'!</h2>
                
                <div class="urgent-notice">
                    <strong>⚠️ Urgent Action Required:</strong> The patient outcome has not yet been submitted. Please update it immediately.
                </div>
                
                <div class="patient-info">
                    <h3>📋 Patient Information</h3>
                    <p><strong>Patient Name:</strong> '.htmlspecialchars($this->patient->name).'</p>
                    <p><strong>Added Since:</strong> '.$this->events->created_at.'</p>
                    <p><strong>Status:</strong> <span style="color: #dc3545; font-weight: bold;">Outcome Pending</span></p>
                </div>
                
                <p>As part of our commitment to quality patient care, we need to ensure all patient outcomes are properly documented and submitted.</p>
                
                <p>Please log into your EGYAKIN account and update the patient outcome as soon as possible.</p>
                
                <div style="text-align: center;">
                    <a href="https://test.egyakin.com" class="cta-button">Update Patient Outcome</a>
                </div>
                
                <p>Thank you for your attention to this matter and for using EGYAKIN!</p>
            </div>
            
            <div class="footer">
                <p>Sincerely,<br>
                <strong>EGYAKIN Scientific Team</strong></p>
                
                <p><small>This is an automated reminder. Please ensure patient outcomes are submitted promptly.</small></p>
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
Patient Outcome Reminder

Hello Doctor '.$notifiable->name.'!

URGENT ACTION REQUIRED: The patient outcome has not yet been submitted. Please update it immediately.

Patient Information:
- Patient Name: '.$this->patient->name.'
- Added Since: '.$this->events->created_at.'
- Status: Outcome Pending

As part of our commitment to quality patient care, we need to ensure all patient outcomes are properly documented and submitted.

Please log into your EGYAKIN account and update the patient outcome as soon as possible.

Visit: https://test.egyakin.com

Thank you for your attention to this matter and for using EGYAKIN!

Sincerely,
EGYAKIN Scientific Team

This is an automated reminder. Please ensure patient outcomes are submitted promptly.
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
