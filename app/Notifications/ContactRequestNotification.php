<?php

namespace App\Notifications;

use App\Services\MailListService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactRequestNotification extends Notification
{
    use Queueable;

    public $message;

    public $subject;

    public $fromEmail;

    public $mailer;

    protected $recipientEmails;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $recipientEmails, string $message)
    {
        $this->subject = 'New Contact Request';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
        $this->recipientEmails = $recipientEmails;
        $this->message = $message;
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
            ->mailer('brevo-api')
            ->subject($this->subject)
            ->greeting('Hello Doctor Mostafa')
            ->line('Dr.'.$notifiable->name.' who works at '.$notifiable->workingplace.' has raised a new contact request.')
            ->line('<< '.$this->message.' >>')
            ->line('He can be reached by Email: '.$notifiable->email.' or Phone: '.$notifiable->phone)
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

        // Get recipients - prioritize ADMIN_MAIL_LIST from .env
        $recipients = [];

        // Always try to use admin mail list from .env first
        $adminEmails = MailListService::getAdminMailList();

        if (! empty($adminEmails)) {
            // Use admin mail list from .env (highest priority)
            $recipients = $adminEmails;
        } elseif (! empty($this->recipientEmails)) {
            // Fallback to provided recipient emails
            $recipients = $this->recipientEmails;
        } else {
            // Final fallback to notifiable email
            $recipients = [$notifiable->email];
        }

        return [
            'to' => $recipients,
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
            <title>EGYAKIN Contact Request</title>
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
                    padding: 60px 40px;
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
                    animation: float 6s ease-in-out infinite;
                }
                
                @keyframes float {
                    0%, 100% { transform: translateY(0px) rotate(0deg); }
                    50% { transform: translateY(-20px) rotate(180deg); }
                }
                
                .contact-icon {
                    font-size: 80px;
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
                    font-size: 32px;
                    margin-bottom: 10px;
                    font-weight: 700;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
                    position: relative;
                    z-index: 1;
                }
                
                .header p {
                    font-size: 18px;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                
                .content {
                    padding: 50px 40px;
                    background: white;
                }
                
                .greeting {
                    font-size: 1.5rem;
                    color: #2d3748;
                    margin-bottom: 25px;
                    font-weight: 600;
                }
                
                .request-summary {
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    border: 2px solid #667eea;
                    border-radius: 16px;
                    padding: 30px;
                    margin: 30px 0;
                    position: relative;
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.1);
                }
                
                .request-summary::before {
                    content: "📞";
                    position: absolute;
                    top: -15px;
                    left: 25px;
                    background: white;
                    padding: 8px 12px;
                    border-radius: 50%;
                    font-size: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .request-summary h3 {
                    color: #667eea;
                    margin-bottom: 15px;
                    font-size: 1.3rem;
                    font-weight: 600;
                }
                
                .request-summary p {
                    color: #4a5568;
                    line-height: 1.7;
                    margin-bottom: 15px;
                }
                
                .message-section {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px;
                    border-radius: 16px;
                    margin: 30px 0;
                    position: relative;
                    overflow: hidden;
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                }
                
                .message-section::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
                    animation: shimmer 3s ease-in-out infinite;
                }
                
                @keyframes shimmer {
                    0% { left: -100%; }
                    100% { left: 100%; }
                }
                
                .message-section h3 {
                    font-size: 1.3rem;
                    margin-bottom: 15px;
                    position: relative;
                    z-index: 1;
                }
                
                .message-content {
                    background: rgba(255, 255, 255, 0.1);
                    padding: 20px;
                    border-radius: 12px;
                    border-left: 4px solid rgba(255, 255, 255, 0.3);
                    font-style: italic;
                    font-size: 1.1rem;
                    position: relative;
                    z-index: 1;
                }
                
                .contact-info {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                    margin: 30px 0;
                    padding: 30px;
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    border-radius: 16px;
                    border: 1px solid #e2e8f0;
                }
                
                .contact-item {
                    background: white;
                    padding: 20px;
                    border-radius: 12px;
                    text-align: center;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                    transition: transform 0.3s ease;
                }
                
                .contact-item:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
                }
                
                .contact-item-icon {
                    font-size: 2rem;
                    margin-bottom: 10px;
                    color: #667eea;
                }
                
                .contact-item-label {
                    font-size: 0.9rem;
                    color: #6c757d;
                    font-weight: 500;
                    margin-bottom: 8px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .contact-item-value {
                    font-size: 1rem;
                    color: #2d3748;
                    font-weight: 600;
                    word-break: break-word;
                }
                
                .action-section {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border: 2px solid #667eea;
                    border-radius: 16px;
                    padding: 30px;
                    margin: 30px 0;
                    text-align: center;
                }
                
                .action-section h3 {
                    color: #667eea;
                    margin-bottom: 15px;
                    font-size: 1.3rem;
                }
                
                .action-section p {
                    color: #4a5568;
                    line-height: 1.7;
                    margin-bottom: 20px;
                }
                
                .cta-button {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px 30px;
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
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
                }
                
                .footer {
                    background-color: #2d3748;
                    color: #a0aec0;
                    padding: 30px 40px;
                    text-align: center;
                    font-size: 0.9rem;
                }
                
                .footer p {
                    color: #a0aec0;
                    margin-bottom: 10px;
                }
                
                .footer strong {
                    color: #667eea;
                    font-weight: 600;
                }
                
                .footer small {
                    color: #718096;
                    font-size: 12px;
                }
                
                @media (max-width: 600px) {
                    .email-container {
                        margin: 10px;
                        border-radius: 15px;
                    }
                    
                    .header, .content {
                        padding: 30px 20px;
                    }
                    
                    .footer {
                        padding: 30px 20px;
                    }
                    
                    .contact-info {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                    
                    .cta-button {
                        padding: 12px 25px;
                        font-size: 14px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="contact-icon">📞</div>
                    <h1>New Contact Request</h1>
                    <p>EGYAKIN Medical Community</p>
                </div>
                
                <div class="content">
                    <div class="greeting">Hello Doctor Mostafa! 👋</div>
                    
                    <div class="request-summary">
                        <h3>Contact Request Received</h3>
                        <p>Dr. <strong>'.htmlspecialchars($notifiable->name).'</strong> from <strong>'.htmlspecialchars($notifiable->workingplace).'</strong> has submitted a new contact request through the EGYAKIN platform.</p>
                    </div>
                    
                    <div class="message-section">
                        <h3>📝 Message from Dr. '.htmlspecialchars($notifiable->name).'</h3>
                        <div class="message-content">
                            "'.htmlspecialchars($this->message).'"
                        </div>
                    </div>
                    
                    <div class="contact-info">
                        <div class="contact-item">
                            <div class="contact-item-icon">👤</div>
                            <div class="contact-item-label">Doctor Name</div>
                            <div class="contact-item-value">Dr. '.htmlspecialchars($notifiable->name).'</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-item-icon">🏥</div>
                            <div class="contact-item-label">Workplace</div>
                            <div class="contact-item-value">'.htmlspecialchars($notifiable->workingplace).'</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-item-icon">📧</div>
                            <div class="contact-item-label">Email Address</div>
                            <div class="contact-item-value">'.htmlspecialchars($notifiable->email).'</div>
                        </div>
                        <div class="contact-item">
                            <div class="contact-item-icon">📱</div>
                            <div class="contact-item-label">Phone Number</div>
                            <div class="contact-item-value">'.htmlspecialchars($notifiable->phone).'</div>
                        </div>
                    </div>
                    
                    <div class="action-section">
                        <h3>Next Steps</h3>
                        <p>Please review the contact request and reach out to Dr. '.htmlspecialchars($notifiable->name).' using the contact information provided above. Timely responses help maintain our professional medical community standards.</p>
                        
                        <a href="mailto:'.htmlspecialchars($notifiable->email).'" class="cta-button">📧 Reply via Email</a>
                    </div>
                    
                    <p style="text-align: center; color: #6c757d; margin-top: 30px;">
                        Thank you for being an active member of the EGYAKIN medical community. 🌟
                    </p>
                </div>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>EGYAKIN Scientific Team</strong></p>
                    
                    <p><small>This contact request was submitted through the EGYAKIN platform. Please respond promptly to maintain professional communication standards.</small></p>
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
📞 NEW CONTACT REQUEST
EGYAKIN Medical Community

Hello Doctor Mostafa! 👋

CONTACT REQUEST RECEIVED:
Dr. '.$notifiable->name.' from '.$notifiable->workingplace.' has submitted a new contact request through the EGYAKIN platform.

📝 MESSAGE FROM DR. '.$notifiable->name.':
"'.$this->message.'"

📋 CONTACT INFORMATION:
👤 Doctor Name: Dr. '.$notifiable->name.'
🏥 Workplace: '.$notifiable->workingplace.'
📧 Email Address: '.$notifiable->email.'
📱 Phone Number: '.$notifiable->phone.'

NEXT STEPS:
Please review the contact request and reach out to Dr. '.$notifiable->name.' using the contact information provided above. Timely responses help maintain our professional medical community standards.

Reply via Email: '.$notifiable->email.'

Thank you for being an active member of the EGYAKIN medical community. 🌟

Best regards,
EGYAKIN Scientific Team

This contact request was submitted through the EGYAKIN platform. Please respond promptly to maintain professional communication standards.
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
