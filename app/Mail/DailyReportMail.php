<?php

namespace App\Mail;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Patients\Models\Patients;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $reportData;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->reportData = $this->generateDailyReportData();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = Carbon::now()->format('Y-m-d');

        return new Envelope(
            subject: "EGYAKIN Daily Report - {$date}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-report',
            with: ['data' => $this->reportData]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate daily report data
     */
    private function generateDailyReportData(): array
    {
        try {
            $yesterday = Carbon::yesterday();
            $today = Carbon::today();

            return [
                'date' => $today->format('F j, Y'),
                'period' => 'Yesterday ('.$yesterday->format('M j, Y').')',

                // User Statistics
                'users' => [
                    'new_registrations' => User::whereBetween('created_at', [$yesterday, $today])->count(),
                    'total_users' => User::count(),
                    'verified_users' => User::whereNotNull('email_verified_at')->count(),
                    'blocked_users' => User::where('blocked', true)->count(),
                    'active_users' => User::where('blocked', false)->where('limited', false)->count(),
                ],

                // Patient Statistics
                'patients' => [
                    'new_patients' => Patients::whereBetween('created_at', [$yesterday, $today])->count(),
                    'total_patients' => Patients::count(),
                    'hidden_patients' => Patients::where('hidden', true)->count(),
                ],

                // Consultation Statistics
                'consultations' => [
                    'new_consultations' => Consultation::whereBetween('created_at', [$yesterday, $today])->count(),
                    'pending_consultations' => Consultation::where('status', 'pending')->count(),
                    'completed_consultations' => Consultation::where('status', 'completed')->count(),
                    'open_consultations' => Consultation::where('is_open', true)->count(),
                ],

                // Feed Activity
                'feed' => [
                    'new_posts' => FeedPost::whereBetween('created_at', [$yesterday, $today])->count(),
                    'total_posts' => FeedPost::count(),
                    'posts_with_media' => FeedPost::whereNotNull('media_path')->count(),
                    'group_posts' => FeedPost::whereNotNull('group_id')->count(),
                ],

                // Group Statistics
                'groups' => [
                    'new_groups' => Group::whereBetween('created_at', [$yesterday, $today])->count(),
                    'total_groups' => Group::count(),
                    'private_groups' => Group::where('privacy', 'private')->count(),
                    'public_groups' => Group::where('privacy', 'public')->count(),
                ],

                // System Health
                'system' => [
                    'database_size' => $this->getDatabaseSize(),
                    'storage_usage' => $this->getStorageUsage(),
                    'last_backup' => $this->getLastBackupDate(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error generating daily report data: '.$e->getMessage());

            return [
                'error' => 'Unable to generate report data',
                'date' => Carbon::now()->format('F j, Y'),
                'period' => 'Data unavailable',
            ];
        }
    }

    /**
     * Get database size (simplified version)
     */
    private function getDatabaseSize(): string
    {
        try {
            // This is a simplified version - you might want to implement actual database size calculation
            return 'N/A';
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    /**
     * Get storage usage (simplified version)
     */
    private function getStorageUsage(): string
    {
        try {
            // This is a simplified version - you might want to implement actual storage calculation
            return 'N/A';
        } catch (\Exception $e) {
            return 'Error';
        }
    }

    /**
     * Get last backup date (placeholder)
     */
    private function getLastBackupDate(): string
    {
        try {
            // This is a placeholder - implement your backup date logic
            return 'N/A';
        } catch (\Exception $e) {
            return 'Error';
        }
    }
}
