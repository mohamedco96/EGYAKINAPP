<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReachingSpecificPoints extends Notification
{
    use Queueable;

    public $message;

    public $subject;

    public $fromEmail;

    public $mailer;

    public $score;

    /**
     * Create a new notification instance.
     */
    public function __construct($score)
    {
        //$this->message = 'Use the below code for verification process';
        $this->subject = 'Congrats from EGYAKIN';
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
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
            ->mailer('brevo-api')
            ->subject($this->subject)
            ->greeting('Hello Doctor '.$notifiable->name)
            ->line('Congrats! You have earned 50 points.')
            ->line('Your score is '.$this->score.' points overall. Keep up your outstanding work.')
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
            <title>EGYAKIN Achievement Unlocked</title>
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
                    animation: shimmer 3s ease-in-out infinite;
                }
                
                @keyframes shimmer {
                    0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(0deg); }
                    50% { transform: translateX(100%) translateY(100%) rotate(180deg); }
                }
                
                .achievement-icon {
                    font-size: 80px;
                    margin-bottom: 15px;
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
                
                .achievement-badge {
                    background: linear-gradient(135deg, #ffd700, #ffb347);
                    color: #2d3748;
                    padding: 15px 25px;
                    border-radius: 50px;
                    display: inline-block;
                    margin: 20px 0;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    font-size: 14px;
                    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
                    animation: glow 2s ease-in-out infinite alternate;
                }
                
                @keyframes glow {
                    from { box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); }
                    to { box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5), 0 0 25px rgba(255, 215, 0, 0.2); }
                }
                
                .congratulations-text {
                    font-size: 1.1rem;
                    color: #4a5568;
                    margin-bottom: 30px;
                    line-height: 1.7;
                }
                
                .score-display {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 30px;
                    border-radius: 20px;
                    text-align: center;
                    margin: 30px 0;
                    position: relative;
                    overflow: hidden;
                    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
                }
                
                .score-display::before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                    animation: scan 3s ease-in-out infinite;
                }
                
                @keyframes scan {
                    0% { left: -100%; }
                    100% { left: 100%; }
                }
                
                .score-display h3 {
                    font-size: 1.3rem;
                    margin-bottom: 15px;
                    font-weight: 600;
                    position: relative;
                    z-index: 1;
                }
                
                .score-number {
                    font-size: 4rem;
                    font-weight: 800;
                    margin: 20px 0;
                    text-shadow: 0 4px 8px rgba(0,0,0,0.3);
                    position: relative;
                    z-index: 1;
                    animation: scoreGlow 2s ease-in-out infinite alternate;
                }
                
                @keyframes scoreGlow {
                    from { text-shadow: 0 4px 8px rgba(0,0,0,0.3); }
                    to { text-shadow: 0 6px 12px rgba(255,255,255,0.5), 0 0 25px rgba(255,255,255,0.2); }
                }
                
                .score-display p {
                    font-size: 1.1rem;
                    opacity: 0.9;
                    position: relative;
                    z-index: 1;
                }
                
                .achievement-details {
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    border: 2px solid #667eea;
                    border-radius: 16px;
                    padding: 30px;
                    margin: 30px 0;
                    position: relative;
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.1);
                }
                
                .achievement-details::before {
                    content: "🏆";
                    position: absolute;
                    top: -15px;
                    left: 25px;
                    background: white;
                    padding: 8px 12px;
                    border-radius: 50%;
                    font-size: 20px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .achievement-details h3 {
                    color: #667eea;
                    margin-bottom: 15px;
                    font-size: 1.3rem;
                    font-weight: 600;
                }
                
                .achievement-details p {
                    color: #4a5568;
                    line-height: 1.7;
                }
                
                .stats-section {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                    margin: 30px 0;
                    padding: 25px;
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    border-radius: 16px;
                }
                
                .stat-item {
                    text-align: center;
                    padding: 15px;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                }
                
                .stat-number {
                    font-size: 2rem;
                    font-weight: 700;
                    color: #667eea;
                    margin-bottom: 5px;
                }
                
                .stat-label {
                    font-size: 0.9rem;
                    color: #6c757d;
                    font-weight: 500;
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
                    
                    .score-number {
                        font-size: 3rem;
                    }
                    
                    .stats-section {
                        grid-template-columns: 1fr;
                        gap: 15px;
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
                    <div class="achievement-icon">🏆</div>
                    <h1>Achievement Unlocked!</h1>
                    <p>Congratulations from EGYAKIN</p>
                </div>
                
                <div class="content">
                    <div class="greeting">Hello Doctor '.htmlspecialchars($notifiable->name).'! 👋</div>
                    
                    <div class="achievement-badge">🎯 New Milestone Reached!</div>
                    
                    <div class="congratulations-text">
                        <p>Outstanding work! You have earned <strong>50 points</strong> for your exceptional contribution to the EGYAKIN medical community. Your dedication to excellence continues to inspire!</p>
                    </div>
                    
                    <div class="score-display">
                        <h3>🌟 Your Total Score</h3>
                        <div class="score-number">'.$this->score.'</div>
                        <p>Points earned through medical excellence!</p>
                    </div>
                    
                    <p style="text-align: center; color: #6c757d; margin-top: 20px;">
                        Keep up the outstanding work! Your contributions make a real difference in healthcare. 🌟
                    </p>
                </div>
                
                <div class="footer">
                    <p>Best regards,<br>
                    <strong>EGYAKIN Scientific Team</strong></p>
                    
                    <p><small>This achievement reflects your commitment to medical excellence and community collaboration. Thank you for being an integral part of our mission!</small></p>
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
🏆 ACHIEVEMENT UNLOCKED!
Congratulations from EGYAKIN

Hello Doctor '.$notifiable->name.'! 👋

🎯 NEW MILESTONE REACHED!

Outstanding work! You have earned 50 points for your exceptional contribution to the EGYAKIN medical community. Your dedication to excellence continues to inspire!

🌟 YOUR TOTAL SCORE: '.$this->score.' POINTS
Points earned through medical excellence!

ACHIEVEMENT DETAILS:
This achievement reflects your commitment to providing exceptional patient care and actively contributing to the medical community. Every point represents a step forward in advancing healthcare through EGYAKIN.

STATS SUMMARY:
🎯 Achievement Unlocked
+50 Points Earned  
'.$this->score.' Total Score

Keep up the outstanding work! Your contributions make a real difference in healthcare. 🌟

Continue your journey: https://test.egyakin.com

Best regards,
EGYAKIN Scientific Team

This achievement reflects your commitment to medical excellence and community collaboration. Thank you for being an integral part of our mission!
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
