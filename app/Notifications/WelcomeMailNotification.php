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
            <title>Welcome to EGYAKIN - Your Medical Practice Revolution</title>
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
                .welcome-icon {
                    font-size: 80px;
                    margin-bottom: 20px;
                    position: relative;
                    z-index: 1;
                    animation: bounce 2s ease-in-out infinite;
                }
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-10px); }
                    60% { transform: translateY(-5px); }
                }
                .header h1 {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin-bottom: 15px;
                    position: relative;
                    z-index: 1;
                }
                .header p {
                    font-size: 1.2rem;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                .content {
                    padding: 50px 40px;
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
                    margin-bottom: 40px;
                    line-height: 1.7;
                }
                .features-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 25px;
                    margin: 40px 0;
                }
                .feature-card {
                    background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
                    padding: 30px 25px;
                    border-radius: 16px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                    border: 1px solid #e2e8f0;
                    transition: transform 0.3s ease, box-shadow 0.3s ease;
                    position: relative;
                    overflow: hidden;
                }
                .feature-card::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 4px;
                    background: linear-gradient(90deg, #667eea, #764ba2);
                }
                .feature-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
                }
                .feature-icon {
                    font-size: 3rem;
                    margin-bottom: 20px;
                    display: block;
                }
                .feature-title {
                    font-size: 1.3rem;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 12px;
                }
                .feature-description {
                    color: #718096;
                    font-size: 1rem;
                    line-height: 1.5;
                }
                .cta-section {
                    text-align: center;
                    margin: 50px 0;
                    padding: 40px;
                    background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
                    border-radius: 16px;
                    border: 1px solid #e2e8f0;
                }
                .cta-button {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 18px 40px;
                    text-decoration: none;
                    border-radius: 50px;
                    display: inline-block;
                    font-weight: 600;
                    font-size: 1.1rem;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
                    transition: all 0.3s ease;
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
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
                }
                .footer {
                    background-color: #2d3748;
                    color: #a0aec0;
                    padding: 30px 40px;
                    text-align: center;
                    font-size: 0.9rem;
                }
                .footer-brand {
                    color: #667eea;
                    font-weight: 600;
                    font-size: 1.1rem;
                    margin-bottom: 10px;
                }
                .stats-section {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin: 40px 0;
                    padding: 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 16px;
                    color: white;
                }
                .stat-item {
                    text-align: center;
                }
                .stat-number {
                    font-size: 2.5rem;
                    font-weight: 700;
                    margin-bottom: 5px;
                }
                .stat-label {
                    font-size: 0.9rem;
                    opacity: 0.9;
                }
                @media (max-width: 600px) {
                    .email-container {
                        margin: 0;
                        border-radius: 0;
                    }
                    .header, .content, .footer {
                        padding: 30px 20px;
                    }
                    .header h1 {
                        font-size: 2rem;
                    }
                    .features-grid {
                        grid-template-columns: 1fr;
                    }
                    .stats-section {
                        grid-template-columns: 1fr;
                    }
                    .cta-button {
                        padding: 15px 30px;
                        font-size: 1rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div class="welcome-icon">🎉</div>
                    <h1>Welcome to EGYAKIN!</h1>
                    <p>Your Medical Practice Revolution Starts Here</p>
                </div>
                
                <div class="content">
                    <div class="greeting">Hello '.htmlspecialchars($notifiable->name ?? 'there').'! 👋</div>
                    
                    <div class="intro-text">
                        We\'re absolutely thrilled to welcome you to EGYAKIN! You\'ve just joined an innovative community of medical professionals who are transforming healthcare through cutting-edge technology.
                    </div>
                    
                    <div class="stats-section">
                        <div class="stat-item">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Active Doctors</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">10K+</div>
                            <div class="stat-label">Patients Served</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <span class="feature-icon">👥</span>
                            <div class="feature-title">Smart Patient Management</div>
                            <div class="feature-description">Organize patient records, track medical history, and manage appointments with our intuitive dashboard.</div>
                        </div>
                        
                        <div class="feature-card">
                            <span class="feature-icon">💬</span>
                            <div class="feature-title">Secure Consultations</div>
                            <div class="feature-description">Connect with colleagues, share medical insights, and collaborate on patient care securely.</div>
                        </div>
                        
                        <div class="feature-card">
                            <span class="feature-icon">📊</span>
                            <div class="feature-title">Advanced Analytics</div>
                            <div class="feature-description">Track your practice performance, monitor patient outcomes, and make data-driven decisions.</div>
                        </div>
                        
                        <div class="feature-card">
                            <span class="feature-icon">🔒</span>
                            <div class="feature-title">HIPAA Compliant</div>
                            <div class="feature-description">Enterprise-grade security with end-to-end encryption and HIPAA compliance built-in.</div>
                        </div>
                        
                        <div class="feature-card">
                            <span class="feature-icon">📱</span>
                            <div class="feature-title">Mobile First</div>
                            <div class="feature-description">Access your practice anywhere with our responsive mobile-optimized interface.</div>
                        </div>
                        
                        <div class="feature-card">
                            <span class="feature-icon">⚡</span>
                            <div class="feature-title">Lightning Fast</div>
                            <div class="feature-description">Optimized for speed with real-time updates and instant synchronization across devices.</div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="footer">
                    <div class="footer-brand">EGYAKIN</div>
                    <div>Empowering Medical Professionals Worldwide</div>
                    <div style="margin-top: 20px; font-size: 0.8rem;">
                        © 2024 EGYAKIN. All rights reserved.
                    </div>
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
🎉 WELCOME TO EGYAKIN! 🎉

Hello '.($notifiable->name ?? 'there').'! 👋

We\'re absolutely thrilled to welcome you to EGYAKIN! You\'ve just joined an innovative community of medical professionals who are transforming healthcare through cutting-edge technology.

═══════════════════════════════════════════════════════════════
📊 PLATFORM STATISTICS
═══════════════════════════════════════════════════════════════
• 500+ Active Doctors
• 10K+ Patients Served  
• 99.9% Uptime

═══════════════════════════════════════════════════════════════
🚀 KEY FEATURES
═══════════════════════════════════════════════════════════════

👥 Smart Patient Management
   Organize patient records, track medical history, and manage appointments with our intuitive dashboard.

💬 Secure Consultations  
   Connect with colleagues, share medical insights, and collaborate on patient care securely.

📊 Advanced Analytics
   Track your practice performance, monitor patient outcomes, and make data-driven decisions.

🔒 HIPAA Compliant
   Enterprise-grade security with end-to-end encryption and HIPAA compliance built-in.

📱 Mobile First
   Access your practice anywhere with our responsive mobile-optimized interface.

⚡ Lightning Fast
   Optimized for speed with real-time updates and instant synchronization across devices.

═══════════════════════════════════════════════════════════════
🚀 GET STARTED NOW
═══════════════════════════════════════════════════════════════

👉 Get Started Now: https://test.egyakin.com

═══════════════════════════════════════════════════════════════
EGYAKIN - Empowering Medical Professionals Worldwide

© 2024 EGYAKIN. All rights reserved.

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
