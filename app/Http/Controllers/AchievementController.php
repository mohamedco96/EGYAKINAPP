<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AchievementController extends Controller
{
    // Dependency injection of NotificationController
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    // Method to create a new achievement
    public function createAchievement(Request $request)
    {
        Log::info('Creating a new achievement', ['request_data' => $request->all()]);

        $achievement = new Achievement($request->all());

        // Check if an image file is uploaded and save its path
        if ($request->hasFile('image')) {
            $achievement->image = $request->file('image')->store('achievement_images', 'public');
            Log::info('Image uploaded for achievement', ['image_path' => $achievement->image]);
        }

        $achievement->save(); // Save the achievement to the database
        Log::info('Achievement created successfully', ['achievement_id' => $achievement->id]);

        return response()->json([
            'message' => 'Achievement created successfully',
            'achievement' => $achievement
        ], 201);
    }

    // Method to assign achievements to all users
    public function checkAndAssignAchievementsForAllUsers()
    {
        Log::info('Starting achievement assignment for all users.');

        // Fetch all achievements
        $achievements = Achievement::all();
        Log::info('Loaded all achievements', ['achievement_count' => $achievements->count()]);

        // Process users in chunks to manage memory and performance
        User::with(['score', 'patients', 'achievements'])->chunk(100, function ($users) use ($achievements) {
            foreach ($users as $user) {
                Log::info('Processing user achievements', ['user_id' => $user->id]);
                $this->processUserAchievements($user, $achievements);
            }
        });

        Log::info('All users processed for achievements.');
    }

    // Helper method to process achievements for a single user
    private function processUserAchievements(User $user, $achievements)
    {
        $userScore = $user->score->score ?? 0; // Get user's score or default to 0
        $userPatientCount = $user->patients->count() ?? 0; // Get user's patient count or default to 0

        Log::info('User data for achievement check', [
            'user_id' => $user->id,
            'score' => $userScore,
            'patient_count' => $userPatientCount
        ]);

        $detachIds = []; // List of achievements to be detached
        $attachData = []; // List of achievements to be attached
        $achievementUpdates = []; // Log updates for debugging

        foreach ($achievements as $achievement) {
            $qualifies = $this->qualifiesForAchievement($userScore, $userPatientCount, $achievement);
            $existingAchievement = $user->achievements->find($achievement->id);

            if ($existingAchievement && !$qualifies) {
                // User no longer qualifies for this achievement
                $detachIds[] = $achievement->id;
                $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => 'removed'];
            } elseif ($qualifies && (!$existingAchievement || !$existingAchievement->pivot->achieved)) {
                // User qualifies for this achievement
                $attachData[$achievement->id] = ['achieved' => true];
                $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => 'achieved'];

                if (!$existingAchievement) {
                    // Notify user if this is a new achievement
                    Log::info('Notifying user about new achievement', [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id
                    ]);
                    $this->notifyAchievement($user, $achievement);
                }
            }
        }

        $this->updateUserAchievements($user, $detachIds, $attachData);
        Log::info('Achievements updated for user', ['user_id' => $user->id, 'updates' => $achievementUpdates]);
    }

    // Helper method to update user achievements
    private function updateUserAchievements(User $user, array $detachIds, array $attachData)
    {
        if (!empty($detachIds)) {
            Log::info('Detaching achievements', ['user_id' => $user->id, 'detach_ids' => $detachIds]);
            $user->achievements()->detach($detachIds);
        }

        if (!empty($attachData)) {
            Log::info('Attaching achievements', ['user_id' => $user->id, 'attach_data' => $attachData]);
            $user->achievements()->syncWithoutDetaching($attachData);
        }
    }

    // Check if a user qualifies for a specific achievement
    private function qualifiesForAchievement(int $userScore, int $userPatientCount, Achievement $achievement): bool
    {
        $result = match ($achievement->type) {
            'score' => $userScore >= $achievement->score,
            'patient' => $userPatientCount >= $achievement->score,
            default => false,
        };

        Log::info('Qualification check', [
            'achievement_id' => $achievement->id,
            'type' => $achievement->type,
            'qualifies' => $result
        ]);

        return $result;
    }

    // Notify user about an achievement
    private function notifyAchievement(User $user, Achievement $achievement)
    {
        $title = 'Achievement Unlocked! 🎉';
        $body = $this->generateNotificationBody($user, $achievement);

        Log::info('Preparing notification', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id
        ]);

        $doctorIds = User::role(['Admin', 'Tester'])->pluck('id');
        $tokens = FcmToken::whereIn('doctor_id', $doctorIds)->pluck('token')->toArray();

        $this->notificationController->sendPushNotification($title, $body, $tokens);
        $this->createAchievementNotification($doctorIds->toArray(), $user->name, $achievement->id, $user->id);
    }

    // Generate the notification body
    private function generateNotificationBody(User $user, Achievement $achievement): string
    {
        $body = 'Dr. ' . $user->name . ' achieved a new milestone: ' . $achievement->name;
        Log::info('Generated notification body', ['body' => $body]);
        return $body;
    }

    // Create a new achievement notification for all relevant doctors
    private function createAchievementNotification(array $doctorIds, string $doctorName, int $achievementId, int $doctorID)
    {
        $notifications = array_map(fn($doctorId) => [
            'doctor_id' => $doctorId,
            'type' => 'Achievement',
            'type_id' => $achievementId,
            'content' => 'Dr. ' . $doctorName . ' earned a new achievement.',
            'type_doctor_id' => $doctorID,
            'created_at' => now(),
            'updated_at' => now(),
        ], $doctorIds);

        Log::info('Inserting notifications', ['notifications' => $notifications]);
        AppNotification::insert($notifications);
    }

    // List all achievements
    public function listAchievements()
    {
        $achievements = Achievement::all();
        Log::info('Listing all achievements', ['achievement_count' => $achievements->count()]);
        return response()->json($achievements, 200);
    }

    // Fetch achievements for a specific user
    public function getUserAchievements(User $user)
    {
        Log::info('Fetching user achievements', ['user_id' => $user->id]);

        $achievedAchievements = $user->achievements()->wherePivot('achieved', 1)->get();

        $transformedAchievements = $achievedAchievements->map(fn($achievement) => [
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
        ]);

        Log::info('User achievements fetched', ['user_id' => $user->id, 'achievement_count' => $transformedAchievements->count()]);

        return response()->json($transformedAchievements, 200);
    }
}
