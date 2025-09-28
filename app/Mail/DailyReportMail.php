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
            $today = Carbon::today();
            $now = Carbon::now();

            return [
                'date' => $today->format('F j, Y'),
                'period' => 'Today ('.$today->format('M j, Y').')',

                // User Statistics
                'users' => [
                    'new_registrations' => User::whereBetween('created_at', [$today, $now])->count(),
                    'total_users' => User::count(),
                    'doctors' => User::where('isSyndicateCardRequired', 'Verified')->count(),
                    'regular_users' => User::where('isSyndicateCardRequired', '!=', 'Verified')->count(),
                    'verified_users' => User::whereNotNull('email_verified_at')->count(),
                    'blocked_users' => User::where('blocked', true)->count(),
                ],

                // Patient Statistics
                'patients' => [
                    'new_patients' => Patients::whereBetween('created_at', [$today, $now])->count(),
                    'total_patients' => Patients::count(),
                    'hidden_patients' => Patients::where('hidden', true)->count(),
                    'submitted_patients' => \App\Modules\Patients\Models\PatientStatus::where('key', 'submit_status')->where('status', true)->count(),
                    'outcome_patients' => \App\Modules\Patients\Models\PatientStatus::where('key', 'outcome_status')->where('status', true)->count(),
                ],

                // Consultation Statistics
                'consultations' => [
                    'new_consultations' => Consultation::whereBetween('created_at', [$today, $now])->count(),
                    'pending_consultations' => Consultation::where('status', 'pending')->count(),
                    'completed_consultations' => Consultation::where('status', 'completed')->count(),
                    'open_consultations' => Consultation::where('is_open', true)->count(),
                    'ai_consultations' => \App\Modules\Chat\Models\AIConsultation::count(),
                    'new_ai_consultations' => \App\Modules\Chat\Models\AIConsultation::whereBetween('created_at', [$today, $now])->count(),
                ],

                // Feed Activity
                'feed' => [
                    'new_posts' => FeedPost::whereBetween('created_at', [$today, $now])->count(),
                    'total_posts' => FeedPost::count(),
                    'posts_with_media' => FeedPost::whereNotNull('media_path')->count(),
                    'group_posts' => FeedPost::whereNotNull('group_id')->count(),
                ],

                // Group Statistics
                'groups' => [
                    'new_groups' => Group::whereBetween('created_at', [$today, $now])->count(),
                    'total_groups' => Group::count(),
                    'private_groups' => Group::where('privacy', 'private')->count(),
                    'public_groups' => Group::where('privacy', 'public')->count(),
                ],

                // Top Performers
                'top_performers' => [
                    'doctors_with_patients' => $this->getDoctorsWithPatients($today, $now),
                    'doctors_with_posts' => $this->getDoctorsWithPosts($today, $now),
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
     * Get doctors who added patients in the given period
     */
    private function getDoctorsWithPatients(Carbon $start, Carbon $end): array
    {
        try {
            return User::select('users.id', 'users.name', 'users.lname', 'users.specialty')
                ->join('patients', 'users.id', '=', 'patients.doctor_id')
                ->where('users.isSyndicateCardRequired', 'Verified')
                ->whereBetween('patients.created_at', [$start, $end])
                ->groupBy('users.id', 'users.name', 'users.lname', 'users.specialty')
                ->orderByRaw('COUNT(patients.id) DESC')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'name' => $user->name.' '.$user->lname,
                        'specialty' => $user->specialty,
                        'patients_count' => $user->patients()->count(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting doctors with patients: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get doctors who created posts in the given period
     */
    private function getDoctorsWithPosts(Carbon $start, Carbon $end): array
    {
        try {
            return User::select('users.id', 'users.name', 'users.lname', 'users.specialty')
                ->join('feed_posts', 'users.id', '=', 'feed_posts.doctor_id')
                ->where('users.isSyndicateCardRequired', 'Verified')
                ->whereBetween('feed_posts.created_at', [$start, $end])
                ->groupBy('users.id', 'users.name', 'users.lname', 'users.specialty')
                ->orderByRaw('COUNT(feed_posts.id) DESC')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'name' => $user->name.' '.$user->lname,
                        'specialty' => $user->specialty,
                        'posts_count' => $user->feedPosts()->count(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting doctors with posts: '.$e->getMessage());

            return [];
        }
    }
}
