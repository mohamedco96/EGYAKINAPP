<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Otp;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    // test
    use Queueable;

    public $message;

    public $subject;

    public $fromEmail;

    public $domain;

    public $otp;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        try {
            $this->message = 'Use the below code for verification process';
            $this->subject = 'EGYAKIN Mail Verification';
            $this->fromEmail = config('mail.from.address');
            $this->domain = config('mail.from.address', 'noreply@egyakin.com');
            $this->otp = new Otp;

            Log::info('EmailVerificationNotification initialized:', [
                'fromEmail' => $this->fromEmail,
                'domain' => $this->domain,
                'brevo_api_configured' => config('services.brevo.api_key') ? 'Yes' : 'No',
                'mail_from_config' => config('mail.from.address'),
            ]);
        } catch (\Exception $e) {
            Log::error('EmailVerificationNotification Construction Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['brevo-api'];
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        try {
            $otp = $this->otp->generate($notifiable->email, 'numeric', 4, 10);

            $htmlContent = $this->getHtmlContent($notifiable, $otp->token);
            $textContent = $this->getTextContent($notifiable, $otp->token);

            Log::info('Preparing to send verification email via Brevo API:', [
                'email' => $notifiable->email,
                'otp_token' => $otp->token,
            ]);

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
            Log::error('Error preparing Brevo API verification email:', [
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
            <title>EGYAKIN Email Verification</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 20px;
                }
                
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                    animation: slideUp 0.6s ease-out;
                }
                
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .header {
                    background: linear-gradient(135deg, #007bff, #0056b3, #004085);
                    color: white;
                    padding: 40px 30px;
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
                
                .logo {
                    font-size: 48px;
                    margin-bottom: 15px;
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
                }
                
                .header p {
                    font-size: 16px;
                    opacity: 0.9;
                }
                
                .content {
                    padding: 40px 30px;
                    background: white;
                }
                
                .welcome-section {
                    text-align: center;
                    margin-bottom: 30px;
                }
                
                .welcome-section h2 {
                    color: #007bff;
                    font-size: 24px;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                
                .welcome-section p {
                    color: #666;
                    font-size: 16px;
                    margin-bottom: 20px;
                }
                
                .otp-container {
                    background: linear-gradient(135deg, #f8f9ff, #e3f2fd);
                    border: 2px solid #e3f2fd;
                    border-radius: 15px;
                    padding: 30px;
                    margin: 30px 0;
                    text-align: center;
                    position: relative;
                    overflow: hidden;
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
                    font-size: 42px;
                    font-weight: 700;
                    color: #007bff;
                    letter-spacing: 8px;
                    margin: 15px 0;
                    text-shadow: 0 2px 4px rgba(0,123,255,0.2);
                    animation: glow 2s ease-in-out infinite alternate;
                }
                
                @keyframes glow {
                    from { text-shadow: 0 2px 4px rgba(0,123,255,0.2); }
                    to { text-shadow: 0 2px 8px rgba(0,123,255,0.4), 0 0 20px rgba(0,123,255,0.1); }
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
                
                .features-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }
                
                .feature-card {
                    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                    border-radius: 12px;
                    padding: 20px;
                    text-align: center;
                    border: 1px solid #dee2e6;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                }
                
                .feature-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                }
                
                .feature-icon {
                    font-size: 32px;
                    margin-bottom: 10px;
                    display: block;
                }
                
                .feature-title {
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 8px;
                }
                
                .feature-desc {
                    font-size: 14px;
                    color: #6c757d;
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
                    background: linear-gradient(135deg, #28a745, #20c997);
                    color: white;
                    padding: 15px 30px;
                    border-radius: 25px;
                    text-decoration: none;
                    display: inline-block;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(40,167,69,0.3);
                }
                
                .cta-button:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(40,167,69,0.4);
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
                
                .social-links {
                    margin: 20px 0;
                }
                
                .social-links a {
                    display: inline-block;
                    margin: 0 10px;
                    color: #6c757d;
                    text-decoration: none;
                    font-size: 20px;
                    transition: color 0.3s ease;
                }
                
                .social-links a:hover {
                    color: #007bff;
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
                    
                    .features-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="logo">🏥</div>
                    <h1>EGYAKIN Email Verification</h1>
                    <p>Welcome to the Future of Medical Practice</p>
                </div>
                
                <div class="content">
                    <div class="welcome-section">
                        <h2>Hello '.htmlspecialchars($notifiable->name).'! 👋</h2>
                        <p>Welcome to EGYAKIN! We\'re thrilled to have you join our innovative medical community.</p>
                        <p>'.htmlspecialchars($this->message).'</p>
                    </div>
                    
                    <div class="otp-container">
                        <div class="otp-label">Your Verification Code</div>
                        <div class="otp-code">'.$otpToken.'</div>
                        <div class="otp-timer">
                            ⏰ This code expires in 10 minutes for your security
                        </div>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <span class="feature-icon">📊</span>
                            <div class="feature-title">Patient Management</div>
                            <div class="feature-desc">Streamline patient records and care coordination</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">💬</span>
                            <div class="feature-title">Medical Consultations</div>
                            <div class="feature-desc">Connect with colleagues and share expertise</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">📝</span>
                            <div class="feature-title">Clinical Documentation</div>
                            <div class="feature-desc">Digital tools for efficient documentation</div>
                        </div>
                        <div class="feature-card">
                            <span class="feature-icon">👥</span>
                            <div class="feature-title">Professional Network</div>
                            <div class="feature-desc">Build connections in the medical community</div>
                        </div>
                    </div>
                    
                    <div class="security-note">
                        <strong>Security & Privacy</strong>
                        <p>Your account is protected with industry-standard security measures. This verification code is unique to your account and will expire automatically.</p>
                    </div>
                    
                    <div class="cta-section">
                        <a href="https://test.egyakin.com/verify" class="cta-button">Complete Verification</a>
                    </div>
                    
                    <p style="text-align: center; color: #6c757d; margin-top: 20px;">
                        Thank you for choosing EGYAKIN for your medical practice! 🚀
                    </p>
                </div>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>EGYAKIN Development Team</strong></p>
                    
                    <div class="social-links">
                        <a href="#">🌐</a>
                        <a href="#">📧</a>
                        <a href="#">📱</a>
                    </div>
                    
                    <p><small>If you did not create an account with EGYAKIN, please ignore this email or contact our support team.</small></p>
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
🏥 EGYAKIN Email Verification
Welcome to the Future of Medical Practice

Hello '.$notifiable->name.'! 👋

Welcome to EGYAKIN! We\'re thrilled to have you join our innovative medical community.

'.$this->message.'

═══════════════════════════════════════════════════════════════
                    YOUR VERIFICATION CODE
                          '.$otpToken.'
═══════════════════════════════════════════════════════════════

⏰ This code expires in 10 minutes for your security

🔒 Security & Privacy:
Your account is protected with industry-standard security measures. 
This verification code is unique to your account and will expire automatically.

🚀 Once verified, you\'ll have full access to:

📊 Patient Management
   Streamline patient records and care coordination

💬 Medical Consultations  
   Connect with colleagues and share expertise

📝 Clinical Documentation
   Digital tools for efficient documentation

👥 Professional Network
   Build connections in the medical community

🎯 Complete your verification: https://test.egyakin.com/verify

Thank you for choosing EGYAKIN for your medical practice! 🚀

═══════════════════════════════════════════════════════════════

Best regards,
EGYAKIN Development Team

🌐 Website | 📧 Email | 📱 Mobile App

If you did not create an account with EGYAKIN, please ignore this email 
or contact our support team.

═══════════════════════════════════════════════════════════════
        ';
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
