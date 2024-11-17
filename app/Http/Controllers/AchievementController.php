<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\NotificationController;

class AchievementController extends Controller
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function createAchievement(Request $request)
    {
        $achievement = new Achievement($request->all());

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('achievement_images', 'public');
            $achievement->image = $imagePath;
        }

        $achievement->save();

        return response()->json(['message' => 'Achievement created successfully', 'achievement' => $achievement], 201);
    }

    public function checkAndAssignAchievementsForAllUsers()
    {
        $users = User::all();
        $achievements = Achievement::all();

        foreach ($users as $user) {
            $userScore = $user->score->score ?? 0;
            $userPatientCount = $user->patients->count() ?? 0;

            $achievementUpdates = [];

            foreach ($achievements as $achievement) {
                $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();
                $qualifies = $this->qualifiesForAchievement($userScore, $userPatientCount, $achievement);

                if ($existingAchievement && !$qualifies) {
                    $user->achievements()->detach($achievement->id);
                    $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => 'removed'];
                }

                $this->assignOrUpdateAchievement($user, $achievement, $qualifies, $existingAchievement, $achievementUpdates);
            }

            Log::info('Achievements processed for user ' . $user->id, $achievementUpdates);
        }

        Log::info('All users processed for achievements.');
    }

    private function qualifiesForAchievement(int $userScore, int $userPatientCount, Achievement $achievement): bool
    {
        switch ($achievement->type) {
            case 'score':
                return $userScore >= $achievement->score;
            case 'patient':
                return $userPatientCount >= $achievement->score;
            default:
                return false;
        }
    }

    private function assignOrUpdateAchievement(User $user, Achievement $achievement, bool $qualifies, $existingAchievement, array &$achievementUpdates)
    {
        if ($qualifies) {
            $status = 'already achieved';

            if (!$existingAchievement) {
                $user->achievements()->attach($achievement->id, ['achieved' => true]);
                $status = 'achieved (new)';
            } elseif (!$existingAchievement->pivot->achieved) {
                $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);
                $status = 'achieved (updated)';
            }

            if ($status !== 'already achieved') {
                $this->notifyAchievement($user, $achievement);
            }

            $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => $status];
        }
    }

    private function notifyAchievement(User $user, Achievement $achievement)
    {
        $title = 'Achievement Unlocked! 🎉';
        $body = $this->generateNotificationBody($user, $achievement);
        $doctors = User::role(['Admin', 'Tester'])->pluck('id');
        $tokens = FcmToken::whereIn('doctor_id', $doctors)->pluck('token')->toArray();

        $this->notificationController->sendPushNotification($title, $body, $tokens);
        $this->createAchievementNotification($doctors->toArray(), $user->name, $achievement->id);
    }

    private function generateNotificationBody(User $user, Achievement $achievement): string
    {
        return 'Dr. ' . $user->name . ' achieved a new milestone: ' . $achievement->name;
    }

    private function createAchievementNotification(array $doctorIds, string $doctorName, int $achievementId)
    {
        foreach ($doctorIds as $doctorId) {
            AppNotification::create([
                'doctor_id' => $doctorId,
                'type' => 'Achievement',
                'type_id' => $achievementId,
                'content' => 'Dr. ' . $doctorName . ' earned a new achievement.',
            ]);
        }
    }

    public function listAchievements()
    {
        $achievements = Achievement::all();
        return response()->json($achievements, 200);
    }

    public function getUserAchievements(User $user)
    {
        $achievedAchievements = $user->achievements()->wherePivot('achieved', 1)->get();

        $transformedAchievements = $achievedAchievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'type' => $achievement->type,
                'score' => (string)$achievement->score,
                'image' => $achievement->image,
                'created_at' => $achievement->created_at,
                'updated_at' => $achievement->updated_at,
                'pivot' => [
                    'user_id' => (string)$achievement->pivot->user_id,
                    'achievement_id' => (string)$achievement->pivot->achievement_id,
                    'achieved' => (string)$achievement->pivot->achieved,
                    'created_at' => $achievement->pivot->created_at,
                    'updated_at' => $achievement->pivot->updated_at,
                ]
            ];
        });

        return response()->json($transformedAchievements, 200);
    }
}

