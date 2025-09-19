<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobMonitoringAlert extends Notification
{
    use Queueable;

    protected $alerts;

    protected $adminEmails;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $alerts, array $adminEmails = [])
    {
        $this->alerts = $alerts;
        $this->adminEmails = $adminEmails;
    }

    /**
     * Get the notification's delivery channels.
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
        $criticalCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'critical'));
        $warningCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'warning'));

        $message = (new MailMessage)
            ->subject('🚨 EGYAKIN Job Monitoring Alert')
            ->greeting('Job Queue Alert')
            ->line('The job monitoring system has detected issues that require attention:')
            ->line('');

        if ($criticalCount > 0) {
            $message->line("🔥 Critical Issues: {$criticalCount}");
        }

        if ($warningCount > 0) {
            $message->line("⚠️ Warnings: {$warningCount}");
        }

        $message->line('');

        foreach ($this->alerts as $alert) {
            $icon = $alert['severity'] === 'critical' ? '🔥' : '⚠️';
            $message->line("{$icon} **{$alert['type']}**: {$alert['message']}");
        }

        $message->line('')
            ->line('Please check the job queue status and take appropriate action.')
            ->action('Check Job Status', url('/admin'))
            ->line('You can also run: php artisan jobs:monitor --stats')
            ->salutation('EGYAKIN Monitoring System');

        return $message;
    }

    /**
     * Get the Brevo API representation of the notification.
     */
    public function toBrevoApi(object $notifiable): array
    {
        $htmlContent = $this->getHtmlContent();
        $textContent = $this->getTextContent();

        // Use admin emails if provided, otherwise fall back to notifiable
        $recipients = ! empty($this->adminEmails) ? $this->adminEmails : [$notifiable->email];

        return [
            'to' => $recipients,
            'subject' => '🚨 EGYAKIN Job Monitoring Alert',
            'htmlContent' => $htmlContent,
            'textContent' => $textContent,
            'from' => [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ],
        ];
    }

    /**
     * Get HTML content for the alert
     */
    private function getHtmlContent(): string
    {
        $criticalCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'critical'));
        $warningCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'warning'));

        $alertsHtml = '';
        foreach ($this->alerts as $alert) {
            $icon = $alert['severity'] === 'critical' ? '🔥' : '⚠️';
            $colorClass = $alert['severity'] === 'critical' ? 'critical' : 'warning';
            $alertsHtml .= "
                <div class='alert {$colorClass}'>
                    <strong>{$icon} {$alert['type']}</strong><br>
                    {$alert['message']}
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <title>Job Monitoring Alert</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 30px; }
                .alert { margin: 15px 0; padding: 15px; border-radius: 5px; border-left: 4px solid; }
                .alert.critical { background: #fee; border-color: #dc3545; color: #721c24; }
                .alert.warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
                .stats { background: white; padding: 20px; border-radius: 5px; margin: 20px 0; }
                .cta { text-align: center; margin: 30px 0; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; }
                .footer { background: #2d3748; color: #a0aec0; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🚨 Job Monitoring Alert</h1>
                <p>EGYAKIN System Monitoring</p>
            </div>
            
            <div class='content'>
                <p>The job monitoring system has detected issues that require attention:</p>
                
                <div class='stats'>
                    ".($criticalCount > 0 ? "<p><strong>🔥 Critical Issues:</strong> {$criticalCount}</p>" : '').'
                    '.($warningCount > 0 ? "<p><strong>⚠️ Warnings:</strong> {$warningCount}</p>" : '')."
                </div>
                
                <h3>Alert Details:</h3>
                {$alertsHtml}
                
                <div class='cta'>
                    <a href='".url('/admin')."' class='button'>Check System Status</a>
                </div>
                
                <p><strong>Recommended Actions:</strong></p>
                <ul>
                    <li>Check the job queue status in the admin panel</li>
                    <li>Review failed job logs for error details</li>
                    <li>Run: <code>php artisan jobs:monitor --stats</code> for detailed information</li>
                    <li>Consider restarting queue workers if needed</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p>EGYAKIN Monitoring System</p>
                <p>Generated: ".now()->format('Y-m-d H:i:s T').'</p>
            </div>
        </body>
        </html>
        ';
    }

    /**
     * Get text content for the alert
     */
    private function getTextContent(): string
    {
        $criticalCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'critical'));
        $warningCount = count(array_filter($this->alerts, fn ($alert) => $alert['severity'] === 'warning'));

        $content = "EGYAKIN JOB MONITORING ALERT\n";
        $content .= "═══════════════════════════════════════\n\n";
        $content .= "The job monitoring system has detected issues that require attention:\n\n";

        if ($criticalCount > 0) {
            $content .= "🔥 Critical Issues: {$criticalCount}\n";
        }

        if ($warningCount > 0) {
            $content .= "⚠️ Warnings: {$warningCount}\n";
        }

        $content .= "\nAlert Details:\n";
        $content .= "─────────────────────────────────────────\n";

        foreach ($this->alerts as $alert) {
            $icon = $alert['severity'] === 'critical' ? '🔥' : '⚠️';
            $content .= "{$icon} {$alert['type']}: {$alert['message']}\n";
        }

        $content .= "\nRecommended Actions:\n";
        $content .= "─────────────────────────────────────────\n";
        $content .= "• Check the job queue status in the admin panel\n";
        $content .= "• Review failed job logs for error details\n";
        $content .= "• Run: php artisan jobs:monitor --stats\n";
        $content .= "• Consider restarting queue workers if needed\n\n";

        $content .= 'Admin Panel: '.url('/admin')."\n";
        $content .= 'Generated: '.now()->format('Y-m-d H:i:s T')."\n\n";
        $content .= 'EGYAKIN Monitoring System';

        return $content;
    }
}
