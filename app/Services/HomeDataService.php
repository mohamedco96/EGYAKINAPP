<?php

namespace App\Services;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Services\PatientService;
use App\Modules\Posts\Models\Posts;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeDataService
{
    private $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * Get all home data for authenticated user
     */
    public function getHomeData(): array
    {
        $user = Auth::user()->load(['roles', 'score', 'patients' => function ($query) {
            $query->where('hidden', false);
        }]);

        $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');
        $isVerified = ! is_null($user->email_verified_at);
        $isSyndicateCardRequired = $user->isSyndicateCardRequired === 'Verified';

        $feedPosts = $this->getFeedPosts($user);
        $counts = $this->getBasicCounts($user);

        $baseResponse = [
            'value' => true,
            'app_update_message' => '<ul><li><strong>Doctor Consultations</strong>: Doctors can now consult one or more colleagues for advice on their patients.</li><li><strong>User Achievements</strong>: Earn achievements by adding a set number of patients or completing specific outcomes.</li></ul>',
            'verified' => $isVerified,
            'unreadCount' => (string) $counts['unreadCount'],
            'doctor_patient_count' => (string) $counts['userPatientCount'],
            'marked_patient_count' => (string) $counts['markedPatientCount'],
            'isSyndicateCardRequired' => $user->isSyndicateCardRequired,
            'isUserBlocked' => $user->blocked,
            'all_patient_count' => (string) $counts['allPatientCount'],
            'score_value' => (string) ($user->score->score ?? 0),
            'posts_count' => (string) $counts['postsCount'],
            'saved_posts_count' => (string) $counts['savedPostsCount'],
            'role' => $user->roles->first()->name ?? 'User',
            'user_type' => $user->user_type,
            'permissions_changed' => $user->permissions_changed ?? false,
        ];

        if (! $isSyndicateCardRequired && ! $isAdminOrTester) {
            return $this->getLimitedHomeData($baseResponse, $feedPosts, $user);
        }

        return $this->getFullHomeData($baseResponse, $feedPosts, $user, $isAdminOrTester);
    }

    /**
     * Get feed posts for user with content and images only
     */
    private function getFeedPosts(User $user): \Illuminate\Support\Collection
    {
        return FeedPost::with([
            'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
            'poll.options' => function ($query) use ($user) {
                $query->withCount('votes')
                    ->with(['votes' => function ($voteQuery) use ($user) {
                        $voteQuery->where('doctor_id', $user->id);
                    }]);
            },
            'likes' => function ($query) use ($user) {
                $query->where('doctor_id', $user->id);
            },
            'saves' => function ($query) use ($user) {
                $query->where('doctor_id', $user->id);
            },
        ])
            ->withCount(['likes', 'comments'])
            ->where('group_id', null)
            ->where('media_type', 'image')
            ->whereNotNull('media_path')
            ->where('media_path', '!=', '[]')
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($post) {
                $post->doctor_id = (int) $post->doctor_id;
                $post->likes_count = (int) $post->likes_count;
                $post->comments_count = (int) $post->comments_count;
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();

                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
                        $option->is_voted = $option->votes->isNotEmpty();
                        unset($option->votes);

                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                unset($post->saves, $post->likes);

                return $post;
            });
    }

    /**
     * Get basic counts for user
     */
    private function getBasicCounts(User $user): array
    {
        return [
            'userPatientCount' => $user->patients()->count(),
            'allPatientCount' => Patients::count(),
            'postsCount' => $user->feedPosts()->whereNull('group_id')->count(),
            'savedPostsCount' => $user->saves()->count(),
            'unreadCount' => AppNotification::where('doctor_id', $user->id)->where('read', false)->count(),
            'markedPatientCount' => $user->markedPatients()->count(),
        ];
    }

    /**
     * Get limited home data for unverified users
     */
    private function getLimitedHomeData(array $baseResponse, $feedPosts, User $user): array
    {
        $trendingHashtags = Hashtag::orderBy('usage_count', 'desc')->limit(5)->get();
        $latestGroups = $this->getLatestGroups($user);

        $baseResponse['data'] = [
            'topDoctors' => [],
            'pendingSyndicateCard' => [],
            'all_patients' => [],
            'current_patient' => [],
            'posts' => [],
            'feed_posts' => $feedPosts,
            'trending_hashtags' => $trendingHashtags,
            'latest_groups' => $latestGroups,
        ];

        return $baseResponse;
    }

    /**
     * Get full home data for verified users
     */
    private function getFullHomeData(array $baseResponse, $feedPosts, User $user, bool $isAdminOrTester): array
    {
        $posts = $this->getPosts();
        $patients = $this->getAllPatients($isAdminOrTester);
        $currentPatients = $this->getCurrentPatients($user, $isAdminOrTester);
        $topDoctors = $this->getTopDoctors();
        $pendingSyndicateCard = $this->getPendingSyndicateCard($isAdminOrTester);

        $transformPatientData = [$this->patientService, 'transformPatientData'];

        $baseResponse['data'] = [
            'topDoctors' => $topDoctors,
            'pendingSyndicateCard' => $pendingSyndicateCard,
            'all_patients' => $patients->map($transformPatientData),
            'current_patient' => $currentPatients->map($transformPatientData),
            'posts' => $posts,
            'feed_posts' => $feedPosts,
            'trending_hashtags' => [],
            'latest_groups' => [],
        ];

        return $baseResponse;
    }

    /**
     * Get all posts with image and content only
     */
    private function getPosts(): \Illuminate\Support\Collection
    {
        return Posts::select('id', 'title', 'image', 'content', 'hidden', 'post_type', 'webinar_date', 'url', 'doctor_id', 'updated_at')
            ->where('hidden', false)
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->with(['doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version'])
            ->get()
            ->map(function ($post) {
                $post->doctor_id = (int) $post->doctor_id;

                return $post;
            });
    }

    /**
     * Get all patients
     */
    private function getAllPatients(bool $isAdminOrTester): \Illuminate\Database\Eloquent\Collection
    {
        return Patients::when(! $isAdminOrTester, fn ($query) => $query->where('hidden', false))
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version',
                'status:id,patient_id,key,status,doctor_id',
                'answers:id,patient_id,answer,question_id',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    /**
     * Get current user's patients
     */
    private function getCurrentPatients(User $user, bool $isAdminOrTester): \Illuminate\Database\Eloquent\Collection
    {
        return $user->patients()
            ->when(! $isAdminOrTester, fn ($query) => $query->where('hidden', false))
            ->with([
                'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version',
                'status:id,patient_id,key,status,doctor_id',
                'answers:id,patient_id,answer,question_id',
            ])
            ->latest('updated_at')
            ->limit(5)
            ->get();
    }

    /**
     * Get top doctors
     */
    private function getTopDoctors(): \Illuminate\Support\Collection
    {
        return User::select(
            'users.id',
            'users.name',
            'users.image',
            'users.syndicate_card',
            'users.isSyndicateCardRequired',
            'users.version',
            'scores.score as score_value'
        )
            ->leftJoin('scores', 'users.id', '=', 'scores.doctor_id')
            ->withCount(['patients', 'posts', 'saves'])
            ->orderByDesc('patients_count')
            ->orderByDesc('scores.score')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image' => $user->image,
                    'syndicate_card' => $user->syndicate_card,
                    'isSyndicateCardRequired' => $user->isSyndicateCardRequired,
                    'version' => $user->version,
                    'patients_count' => (string) $user->patients_count,
                    'score' => (string) ($user->score_value ?? 0),
                    'posts_count' => (string) $user->posts_count,
                    'saved_posts_count' => (string) $user->saves_count,
                ];
            });
    }

    /**
     * Get pending syndicate card users
     */
    private function getPendingSyndicateCard(bool $isAdminOrTester): \Illuminate\Support\Collection
    {
        return $isAdminOrTester
            ? User::select('id', 'name', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->where('isSyndicateCardRequired', 'Pending')
                ->limit(10)
                ->get()
            : collect();
    }

    /**
     * Get latest groups for user
     */
    private function getLatestGroups(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $latestGroups = Group::with(['owner:id,name,lname,image,syndicate_card,isSyndicateCardRequired,version'])
            ->whereDoesntHave('doctors', function ($query) use ($user) {
                $query->where('doctor_id', $user->id)
                    ->where('status', 'joined');
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $groupUserStatuses = DB::table('group_user')
            ->where('doctor_id', $user->id)
            ->whereIn('group_id', $latestGroups->pluck('id'))
            ->pluck('status', 'group_id');

        $groupMemberCounts = DB::table('group_user')
            ->whereIn('group_id', $latestGroups->pluck('id'))
            ->where('status', 'joined')
            ->selectRaw('group_id, COUNT(*) as count')
            ->groupBy('group_id')
            ->pluck('count', 'group_id');

        $latestGroups->each(function ($group) use ($groupUserStatuses, $groupMemberCounts) {
            $group->user_status = $groupUserStatuses[$group->id] ?? null;
            $group->member_count = (int) ($groupMemberCounts[$group->id] ?? 0);
        });

        return $latestGroups;
    }
}
