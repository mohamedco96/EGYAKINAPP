<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public $message;

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
        $this->mailer = 'brevo-api';
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
            <title>EGYAKIN Patient Outcome Reminder</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    line-height: 1.6;
                    color: #2d3748;
                    background-color: #f7fafc;
                    margin: 0;
                    padding: 0;
                }
                
                .email-container {
                    max-width: 650px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                    border-radius: 12px;
                    overflow: hidden;
                }
                
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 50px 30px;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
                }
                
                .header::before {
                    content: "";
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
                    animation: shimmer 3s ease-in-out infinite;
                }
                
                @keyframes shimmer {
                    0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(0deg); }
                    50% { transform: translateX(100%) translateY(100%) rotate(180deg); }
                }
                
                .reminder-icon {
                    font-size: 60px;
                    margin-bottom: 15px;
                    position: relative;
                    z-index: 1;
                    animation: pulse 2s ease-in-out infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                }
                
                .header h1 {
                    font-size: 28px;
                    margin-bottom: 10px;
                    font-weight: 700;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                    position: relative;
                    z-index: 1;
                }
                
                .header p {
                    font-size: 16px;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                
                .content {
                    padding: 40px 30px;
                    background: white;
                }
                
                .greeting {
                    font-size: 1.5rem;
                    color: #2d3748;
                    margin-bottom: 25px;
                    font-weight: 600;
                }
                
                .urgent-notice {
                    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
                    border: 2px solid #ffc107;
                    border-radius: 16px;
                    padding: 25px;
                    margin: 30px 0;
                    position: relative;
                    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.2);
                }
                
                .urgent-notice::before {
                    content: "⚠️";
                    position: absolute;
                    top: -15px;
                    left: 25px;
                    background: white;
                    padding: 8px 12px;
                    border-radius: 50%;
                    font-size: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .urgent-notice strong {
                    color: #856404;
                    display: block;
                    margin-bottom: 10px;
                    font-size: 1.1rem;
                }
                
                .urgent-notice p {
                    color: #856404;
                    font-size: 1rem;
                    margin: 0;
                }
                
                .patient-info {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border: 2px solid #667eea;
                    border-radius: 16px;
                    padding: 30px;
                    margin: 30px 0;
                    position: relative;
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.1);
                }
                
                .patient-info::before {
                    content: "📋";
                    position: absolute;
                    top: -15px;
                    left: 25px;
                    background: white;
                    padding: 8px 12px;
                    border-radius: 50%;
                    font-size: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .patient-info h3 {
                    color: #667eea;
                    margin-bottom: 20px;
                    font-size: 1.3rem;
                    font-weight: 600;
                }
                
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 0;
                    border-bottom: 1px solid #e2e8f0;
                }
                
                .info-row:last-child {
                    border-bottom: none;
                }
                
                .info-label {
                    font-weight: 600;
                    color: #4a5568;
                }
                
                .info-value {
                    color: #2d3748;
                    font-weight: 500;
                }
                
                .status-badge {
                    background: linear-gradient(135deg, #e53e3e, #c53030);
                    color: white;
                    padding: 6px 16px;
                    border-radius: 20px;
                    font-size: 0.9rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .intro-text {
                    font-size: 1.1rem;
                    color: #4a5568;
                    margin-bottom: 30px;
                    line-height: 1.7;
                }
                
                .cta-section {
                    text-align: center;
                    margin: 40px 0;
                }
                
                .cta-button {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 18px 40px;
                    border-radius: 50px;
                    text-decoration: none;
                    display: inline-block;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    position: relative;
                    overflow: hidden;
                }
                
                .cta-button::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    transition: left 0.5s;
                }
                
                .cta-button:hover::before {
                    left: 100%;
                }
                
                .cta-button:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
                }
                
                .footer {
                    background: #f8f9fa;
                    padding: 30px;
                    text-align: center;
                    border-top: 1px solid #dee2e6;
                }
                
                .footer p {
                    color: #6c757d;
                    margin-bottom: 10px;
                }
                
                .footer strong {
                    color: #495057;
                }
                
                .footer small {
                    color: #adb5bd;
                    font-size: 12px;
                }
                
                @media (max-width: 600px) {
                    .email-container {
                        margin: 10px;
                        border-radius: 15px;
                    }
                    
                    .header, .content, .footer {
                        padding: 20px;
                    }
                    
                    .info-row {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 5px;
                    }
                    
                    .cta-button {
                        padding: 15px 30px;
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="reminder-icon">⏰</div>
                    <h1>EGYAKIN Patient Reminder</h1>
                    <p>Action Required - Please Update Patient Status</p>
                </div>
                
                <div class="content">
                    <div class="greeting">Hello Doctor '.htmlspecialchars($notifiable->name).'! 👋</div>
                    
                    <div class="urgent-notice">
                        <strong>Urgent Action Required</strong>
                        <p>The patient outcome has not yet been submitted. Please update it immediately to ensure proper patient care documentation.</p>
                    </div>
                    
                    <div class="patient-info">
                        <h3>Patient Information</h3>
                        <div class="info-row">
                            <span class="info-label">Patient Name:</span>
                            <span class="info-value">'.htmlspecialchars($this->patient->name).'</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Added Since:</span>
                            <span class="info-value">'.$this->events->created_at.'</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="status-badge">Outcome Pending</span>
                        </div>
                    </div>
                    
                    <div class="intro-text">
                        As part of our commitment to quality patient care, we need to ensure all patient outcomes are properly documented and submitted. This helps maintain accurate medical records and improves patient care quality.
                    </div>
                    

                    
                    <p style="text-align: center; color: #6c757d; margin-top: 20px;">
                        Thank you for your attention to this matter and for using EGYAKIN! 🚀
                    </p>
                </div>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>EGYAKIN Scientific Team</strong></p>
                    
                    <p><small>This is an automated reminder. Please ensure patient outcomes are submitted promptly to maintain quality care standards.</small></p>
                </div>
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
EGYAKIN Patient Outcome Reminder

Hello Doctor '.$notifiable->name.'! 👋

⚠️ URGENT ACTION REQUIRED: The patient outcome has not yet been submitted. Please update it immediately to ensure proper patient care documentation.

📋 Patient Information:
- Patient Name: '.$this->patient->name.'
- Added Since: '.$this->events->created_at.'
- Status: Outcome Pending

As part of our commitment to quality patient care, we need to ensure all patient outcomes are properly documented and submitted. This helps maintain accurate medical records and improves patient care quality.

Please log into your EGYAKIN account and update the patient outcome as soon as possible.

Visit: https://test.egyakin.com

Thank you for your attention to this matter and for using EGYAKIN! 🚀

Best regards,
EGYAKIN Scientific Team

This is an automated reminder. Please ensure patient outcomes are submitted promptly to maintain quality care standards.
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
