<?php

namespace App\Mail;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostLike;
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

class WeeklySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $summaryData;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        $this->summaryData = $this->generateWeeklySummaryData();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Use previous week for the subject line
        $weekStart = Carbon::now()->subWeek()->startOfWeek()->format('M j');
        $weekEnd = Carbon::now()->subWeek()->endOfWeek()->format('M j, Y');

        return new Envelope(
            subject: __('api.weekly_summary_subject', ['week_start' => $weekStart, 'week_end' => $weekEnd]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-summary',
            with: ['data' => $this->summaryData]
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
     * Generate weekly summary data
     */
    private function generateWeeklySummaryData(): array
    {
        try {
            // Use previous week as the main reporting week
            $weekStart = Carbon::now()->subWeek()->startOfWeek();
            $weekEnd = Carbon::now()->subWeek()->endOfWeek();
            // Week before previous week for comparison
            $lastWeekStart = Carbon::now()->subWeeks(2)->startOfWeek();
            $lastWeekEnd = Carbon::now()->subWeeks(2)->endOfWeek();

            // Previous week data (main report)
            $currentWeek = $this->getWeeklyStats($weekStart, $weekEnd);
            $lastWeek = $this->getWeeklyStats($lastWeekStart, $lastWeekEnd);

            return [
                'week_period' => $weekStart->format('M j').' - '.$weekEnd->format('M j, Y'),
                'current_week' => $currentWeek,
                'last_week' => $lastWeek,
                'growth' => $this->calculateGrowth($currentWeek, $lastWeek),

                // Top performers for previous week
                'top_performers' => [
                    'most_active_doctors' => $this->getMostActiveDoctors($weekStart, $weekEnd),
                    'doctors_with_patients' => $this->getDoctorsWithPatients($weekStart, $weekEnd),
                    'doctors_with_posts' => $this->getDoctorsWithPosts($weekStart, $weekEnd),
                    'popular_posts' => $this->getPopularPosts($weekStart, $weekEnd),
                    'active_groups' => $this->getMostActiveGroups($weekStart, $weekEnd),
                ],

                // Trends and insights for previous week
                'trends' => [
                    'user_engagement' => $this->getUserEngagementTrend($weekStart, $weekEnd),
                    'consultation_patterns' => $this->getConsultationPatterns($weekStart, $weekEnd),
                    'content_performance' => $this->getContentPerformance($weekStart, $weekEnd),
                ],

                // System overview
                'system_overview' => [
                    'total_users' => User::count(),
                    'total_doctors' => User::where('isSyndicateCardRequired', 'Verified')->count(),
                    'total_patients' => Patients::count(),
                    'total_consultations' => Consultation::count(),
                    'total_ai_consultations' => \App\Modules\Chat\Models\AIConsultation::count(),
                    'total_posts' => FeedPost::count(),
                    'total_groups' => Group::count(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error generating weekly summary data: '.$e->getMessage());

            return [
                'error' => 'Unable to generate summary data',
                'week_period' => Carbon::now()->subWeek()->startOfWeek()->format('M j').' - '.Carbon::now()->subWeek()->endOfWeek()->format('M j, Y'),
            ];
        }
    }

    /**
     * Get weekly statistics for a given period
     */
    private function getWeeklyStats(Carbon $start, Carbon $end): array
    {
        return [
            'new_users' => User::whereBetween('created_at', [$start, $end])->count(),
            'new_doctors' => User::where('isSyndicateCardRequired', 'Verified')->whereBetween('created_at', [$start, $end])->count(),
            'new_patients' => Patients::whereBetween('created_at', [$start, $end])->count(),
            'new_consultations' => Consultation::whereBetween('created_at', [$start, $end])->count(),
            'new_ai_consultations' => \App\Modules\Chat\Models\AIConsultation::whereBetween('created_at', [$start, $end])->count(),
            'new_posts' => FeedPost::whereBetween('created_at', [$start, $end])->count(),
            'new_groups' => Group::whereBetween('created_at', [$start, $end])->count(),
            'total_likes' => FeedPostLike::whereBetween('created_at', [$start, $end])->count(),
            'total_comments' => FeedPostComment::whereBetween('created_at', [$start, $end])->count(),
        ];
    }

    /**
     * Calculate growth percentages
     */
    private function calculateGrowth(array $current, array $last): array
    {
        $growth = [];
        foreach ($current as $key => $value) {
            $lastValue = $last[$key] ?? 0;
            if ($lastValue > 0) {
                $growth[$key] = round((($value - $lastValue) / $lastValue) * 100, 1);
            } else {
                $growth[$key] = $value > 0 ? 100 : 0;
            }
        }

        return $growth;
    }

    /**
     * Get most active doctors this week
     */
    private function getMostActiveDoctors(Carbon $start, Carbon $end): array
    {
        try {
            return User::select('users.id', 'users.name', 'users.lname', 'users.specialty')
                ->join('feed_posts', 'users.id', '=', 'feed_posts.doctor_id')
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
            Log::error('Error getting most active doctors: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get popular posts this week
     */
    private function getPopularPosts(Carbon $start, Carbon $end): array
    {
        try {
            return FeedPost::with('doctor:id,name,lname')
                ->whereBetween('created_at', [$start, $end])
                ->withCount(['likes', 'comments'])
                ->orderByDesc('likes_count')
                ->limit(5)
                ->get()
                ->map(function ($post) {
                    return [
                        'content' => substr($post->content, 0, 100).'...',
                        'author' => $post->doctor->name.' '.$post->doctor->lname,
                        'likes' => $post->likes_count,
                        'comments' => $post->comments_count,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting popular posts: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get most active groups this week
     */
    private function getMostActiveGroups(Carbon $start, Carbon $end): array
    {
        try {
            return Group::select('groups.id', 'groups.name', 'groups.privacy')
                ->join('feed_posts', 'groups.id', '=', 'feed_posts.group_id')
                ->whereBetween('feed_posts.created_at', [$start, $end])
                ->groupBy('groups.id', 'groups.name', 'groups.privacy')
                ->orderByRaw('COUNT(feed_posts.id) DESC')
                ->limit(5)
                ->get()
                ->map(function ($group) {
                    return [
                        'name' => $group->name,
                        'privacy' => $group->privacy,
                        'posts_count' => $group->posts()->count(),
                        'members_count' => $group->doctors()->count(),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting most active groups: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get user engagement trend
     */
    private function getUserEngagementTrend(Carbon $start, Carbon $end): array
    {
        $activeUsers = User::whereHas('feedPosts', function ($query) use ($start, $end) {
            $query->whereBetween('created_at', [$start, $end]);
        })->count();

        $totalUsers = User::count();
        $engagementRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0;

        return [
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'engagement_rate' => $engagementRate,
        ];
    }

    /**
     * Get consultation patterns
     */
    private function getConsultationPatterns(Carbon $start, Carbon $end): array
    {
        $consultations = Consultation::whereBetween('created_at', [$start, $end]);

        return [
            'total_consultations' => $consultations->count(),
            'pending' => $consultations->where('status', 'pending')->count(),
            'completed' => $consultations->where('status', 'completed')->count(),
            'average_response_time' => 'N/A', // You can implement actual calculation
        ];
    }

    /**
     * Get content performance
     */
    private function getContentPerformance(Carbon $start, Carbon $end): array
    {
        $posts = FeedPost::whereBetween('created_at', [$start, $end]);
        $totalPosts = $posts->count();

        return [
            'total_posts' => $totalPosts,
            'posts_with_media' => $posts->whereNotNull('media_path')->count(),
            'average_likes_per_post' => $totalPosts > 0 ? round(FeedPostLike::whereBetween('created_at', [$start, $end])->count() / $totalPosts, 1) : 0,
            'average_comments_per_post' => $totalPosts > 0 ? round(FeedPostComment::whereBetween('created_at', [$start, $end])->count() / $totalPosts, 1) : 0,
        ];
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
