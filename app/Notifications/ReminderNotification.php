<?php

namespace App\Notifications;

use App\Models\Answers;
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

    protected $patientName;

    /**
     * Create a new notification instance.
     */
    public function __construct($patient, $events)
    {
        //$this->message = 'Use the below code for verification process';
        $this->subject = __('api.reminder_from_egyakin');
        $this->fromEmail = 'noreply@egyakin.com';
        $this->mailer = 'brevo-api';
        $this->patient = $patient;
        $this->events = $events;

        // Get patient name from answers table where question_id = 1
        $this->patientName = $this->getPatientNameFromAnswers();
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
            ->greeting(__('api.hello_doctor', ['name' => $notifiable->name]))
            ->line(__('api.patient_outcome_not_submitted', ['patient' => $this->patientName]))
            ->line(__('api.patient_added_since', ['date' => $this->events->created_at]))
            ->line(__('api.thank_you_using_application'))
            ->line(__('api.sincerely'))
            ->salutation(__('api.egyakin_scientific_team'));
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
                    <div class="greeting">'.__('api.hello_doctor', ['name' => htmlspecialchars($notifiable->name)]).' 👋</div>
                    
                    <div class="urgent-notice">
                        <strong>'.__('api.urgent_action_required').'</strong>
                        <p>'.__('api.patient_outcome_pending_message').'</p>
                    </div>
                    
                    <div class="patient-info">
                        <h3>'.__('api.patient_information').'</h3>
                        <div class="info-row">
                            <span class="info-label">'.__('api.patient_name').':</span>
                            <span class="info-value">'.htmlspecialchars($this->patientName).'</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">'.__('api.added_since').':</span>
                            <span class="info-value">'.$this->events->created_at.'</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">'.__('api.status').':</span>
                            <span class="status-badge">'.__('api.outcome_pending').'</span>
                        </div>
                    </div>
                    
                    <div class="intro-text">
                        '.__('api.quality_care_commitment').'
                    </div>
                    

                    
                    <p style="text-align: center; color: #6c757d; margin-top: 20px;">
                        '.__('api.thank_you_attention').'
                    </p>
                </div>
                
                <div class="footer">
                    <p>'.__('api.best_regards').'<br>
                    <strong>'.__('api.egyakin_scientific_team').'</strong></p>
                    
                    <p><small>'.__('api.automated_reminder').'</small></p>
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

'.__('api.hello_doctor', ['name' => $notifiable->name]).' 👋

⚠️ '.__('api.urgent_action_required').': '.__('api.patient_outcome_pending_message').'

📋 '.__('api.patient_information').':
- '.__('api.patient_name').': '.$this->patientName.'
- '.__('api.added_since').': '.$this->events->created_at.'
- '.__('api.status').': '.__('api.outcome_pending').'

'.__('api.quality_care_commitment').'

Please log into your EGYAKIN account and update the patient outcome as soon as possible.

Visit: https://test.egyakin.com

'.__('api.thank_you_attention').'

'.__('api.best_regards').',
'.__('api.egyakin_scientific_team').'

'.__('api.automated_reminder').'
        ';
    }

    /**
     * Get patient name from answers table where question_id = 1
     */
    private function getPatientNameFromAnswers(): string
    {
        try {
            // Get the patient ID from either the patient object or events object
            $patientId = null;

            if ($this->patient && isset($this->patient->id)) {
                $patientId = $this->patient->id;
            } elseif ($this->events && isset($this->events->patient_id)) {
                $patientId = $this->events->patient_id;
            }

            if (! $patientId) {
                return __('api.unknown_patient');
            }

            // Get the patient name from answers table where question_id = 1
            $answer = Answers::where('patient_id', $patientId)
                ->where('question_id', 1)
                ->first();

            if ($answer && ! empty($answer->answer)) {
                // Handle if answer is stored as JSON array or plain text
                if (is_array($answer->answer)) {
                    return $answer->answer[0] ?? __('api.unknown_patient');
                } elseif (is_string($answer->answer)) {
                    // Try to decode JSON if it's a JSON string
                    $decoded = json_decode($answer->answer, true);
                    if (is_array($decoded)) {
                        return $decoded[0] ?? $answer->answer;
                    }

                    return $answer->answer;
                }
            }

            return __('api.unknown_patient');
        } catch (\Exception $e) {
            // Log the error but don't fail the notification
            \Log::warning('Failed to get patient name from answers', [
                'patient_id' => $patientId ?? null,
                'error' => $e->getMessage(),
            ]);

            return __('api.unknown_patient');
        }
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
