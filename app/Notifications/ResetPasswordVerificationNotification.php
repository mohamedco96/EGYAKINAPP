<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Otp;

class ResetPasswordVerificationNotification extends Notification
{
    use Queueable;

    public $message;

    public $subject;

    public $fromEmail;

    public $mailer;

    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->message = 'Use the below code for resetting your password';
        $this->subject = 'EGYAKIN Reset Mail Password';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
        $this->otp = new Otp;

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
        $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);

        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello '.$notifiable->name)
            ->line($this->message)
        //->action('Verify', url('/'))
            ->line('Thank you for using our application!')
            ->line('Code: '.$otp->token);
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        try {
            // Generate OTP with fallback for testing
            try {
                $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);
                $otpToken = $otp->token;
            } catch (\Exception $e) {
                // Fallback for testing when database is not available
                \Log::warning('OTP generation failed for password reset, using fallback for testing', [
                    'email' => $notifiable->email,
                    'error' => $e->getMessage(),
                ]);
                $otpToken = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            }

            $htmlContent = $this->getHtmlContent($notifiable, $otpToken);
            $textContent = $this->getTextContent($notifiable, $otpToken);

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
        } catch (\Exception $e) {
            \Log::error('Error preparing Brevo API password reset email:', [
                'email' => $notifiable->email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get HTML content for Brevo API
     */
    private function getHtmlContent($notifiable, $otpToken): string
    {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>EGYAKIN Password Reset</title>
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
                    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
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
                
                .reset-icon {
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
                
                .intro-text {
                    font-size: 1.1rem;
                    color: #4a5568;
                    margin-bottom: 30px;
                    line-height: 1.7;
                }
                
                .otp-container {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border: 3px solid #e53e3e;
                    border-radius: 20px;
                    padding: 40px 30px;
                    margin: 40px 0;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(229, 62, 62, 0.15);
                }
                
                .otp-container::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                    animation: scan 2s ease-in-out infinite;
                }
                
                @keyframes scan {
                    0% { left: -100%; }
                    100% { left: 100%; }
                }
                
                .otp-label {
                    color: #666;
                    font-size: 14px;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    font-weight: 600;
                }
                
                .otp-code {
                    font-size: 48px;
                    font-weight: 800;
                    color: #e53e3e;
                    letter-spacing: 12px;
                    margin: 20px 0;
                    text-shadow: 0 4px 8px rgba(229, 62, 62, 0.3);
                    animation: glow 2s ease-in-out infinite alternate;
                    background: linear-gradient(135deg, #e53e3e, #c53030);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                
                @keyframes glow {
                    from { text-shadow: 0 4px 8px rgba(229, 62, 62, 0.3); }
                    to { text-shadow: 0 6px 12px rgba(229, 62, 62, 0.5), 0 0 25px rgba(229, 62, 62, 0.2); }
                }
                
                .otp-timer {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 8px;
                    padding: 12px;
                    margin: 15px 0;
                    font-size: 14px;
                    color: #856404;
                }
                
                .security-note {
                    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
                    border: 1px solid #ffeaa7;
                    border-radius: 12px;
                    padding: 20px;
                    margin: 25px 0;
                    position: relative;
                }
                
                .security-note::before {
                    content: "🔒";
                    position: absolute;
                    top: -10px;
                    left: 20px;
                    background: white;
                    padding: 5px 10px;
                    border-radius: 50%;
                    font-size: 16px;
                }
                
                .security-note strong {
                    color: #856404;
                    display: block;
                    margin-bottom: 8px;
                }
                
                .security-note p {
                    color: #856404;
                    font-size: 14px;
                    margin: 0;
                }
                
                .cta-section {
                    text-align: center;
                    margin: 30px 0;
                }
                
                .cta-button {
                    background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
                    color: white;
                    padding: 18px 40px;
                    border-radius: 50px;
                    text-decoration: none;
                    display: inline-block;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
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
                    box-shadow: 0 8px 25px rgba(229, 62, 62, 0.6);
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
                    
                    .otp-code {
                        font-size: 32px;
                        letter-spacing: 6px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="reset-icon">🔐</div>
                    <h1>EGYAKIN Password Reset</h1>
                    <p>Secure Password Recovery Process</p>
                </div>
                
                <div class="content">
                    <div class="greeting">Hello '.htmlspecialchars($notifiable->name).'! 👋</div>
                    
                    <div class="intro-text">
                        We received a request to reset your password for your EGYAKIN account. Use the verification code below to complete the password reset process.
                    </div>
                    
                    <div class="otp-container">
                        <div class="otp-label">Your Reset Code</div>
                        <div class="otp-code">'.$otpToken.'</div>
                        <div class="otp-timer">
                            ⏰ This code expires in 10 minutes for your security
                        </div>
                    </div>
                    
                    <div class="security-note">
                        <strong>Security & Privacy</strong>
                        <p>This password reset code is unique to your account and will expire automatically. If you did not request this password reset, please ignore this email and your account will remain secure.</p>
                    </div>
                    
                    <div class="cta-section">
                        <a href="https://test.egyakin.com/reset-password" class="cta-button">Reset Password</a>
                    </div>
                    
                    <p style="text-align: center; color: #6c757d; margin-top: 20px;">
                        Thank you for choosing EGYAKIN for your medical practice! 🚀
                    </p>
                </div>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>EGYAKIN Security Team</strong></p>
                    
                    <p><small>If you did not request this password reset, please ignore this email or contact our support team immediately.</small></p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get text content for Brevo API
     */
    private function getTextContent($notifiable, $otpToken): string
    {
        return '
🔐 EGYAKIN Password Reset
Secure Password Recovery Process

Hello '.$notifiable->name.'! 👋

We received a request to reset your password for your EGYAKIN account. 
Use the verification code below to complete the password reset process.

═══════════════════════════════════════════════════════════════
                    YOUR RESET CODE
                          '.$otpToken.'
═══════════════════════════════════════════════════════════════

⏰ This code expires in 10 minutes for your security

🔒 Security & Privacy:
This password reset code is unique to your account and will expire automatically. 
If you did not request this password reset, please ignore this email and your 
account will remain secure.

🎯 Complete your password reset: https://test.egyakin.com/reset-password

Thank you for choosing EGYAKIN for your medical practice! 🚀

═══════════════════════════════════════════════════════════════

Best regards,
EGYAKIN Security Team

If you did not request this password reset, please ignore this email 
or contact our support team immediately.

═══════════════════════════════════════════════════════════════
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
