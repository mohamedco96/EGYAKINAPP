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
        // Retrieve all users
        $users = User::all();

        // Fetch all achievements from the database
        $achievements = Achievement::all();

        foreach ($users as $user) {
            // Retrieve the user's total score and patient count
            $userScore = $user->score->score ?? 0;
            $userPatientCount = $user->patients->count() ?? 0;

            // Log the initial score and patient count for each user
            Log::debug('Processing achievements for user', [
                'user_id' => $user->id,
                'user_score' => $userScore,
                'user_patient_count' => $userPatientCount,
            ]);

            // Initialize an array to store achievement updates for logging
            $achievementLog = [];

            // Loop through each achievement
            foreach ($achievements as $achievement) {
                // Check if the user already has this achievement
                $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();

                // Log the existing achievement status
                Log::debug('Checking achievement', [
                    'achievement_id' => $achievement->id,
                    'existing_achievement' => $existingAchievement ? 'exists' : 'does not exist',
                ]);

                // Determine whether the user qualifies for the achievement
                $qualifies = false;
                $body = ''; // Initialize body for notification
                switch ($achievement->type) {
                    case 'score':
                        $qualifies = $userScore >= $achievement->score;
                        Log::debug('Score qualification check', [
                            'achievement_id' => $achievement->id,
                            'user_score' => $userScore,
                            'required_score' => $achievement->score,
                            'qualifies' => $qualifies,
                        ]);
                        break;

                    case 'patient':
                        $qualifies = $userPatientCount >= $achievement->score;
                        $body = 'Dr. ' . $user->name . ' successfully added ' . $achievement->score . ' patients and earned a new achievement. Keep up the great work!';
                        Log::debug('Patient qualification check', [
                            'achievement_id' => $achievement->id,
                            'user_patient_count' => $userPatientCount,
                            'required_patient_count' => $achievement->score,
                            'qualifies' => $qualifies,
                        ]);
                        break;
                }

                // Remove incorrect achievements if the user no longer qualifies
                if ($existingAchievement && !$qualifies) {
                    $user->achievements()->detach($achievement->id);
                    $achievementLog[] = [
                        'achievement_id' => $achievement->id,
                        'status' => 'removed (no longer qualifies)',
                    ];
                    Log::info('Achievement removed', [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                    ]);
                }

                // Get the IDs of doctors for notifications
                $doctors = User::role(['Admin', 'Tester'])->pluck('id');
                $title = 'Achievement Unlocked! 🎉';
                $tokens = FcmToken::whereIn('doctor_id', $doctors)->pluck('token')->toArray();

                // Assign or update achievements if the user qualifies
                if ($qualifies) {
                    if (!$existingAchievement) {
                        $user->achievements()->attach($achievement->id, ['achieved' => true]);
                        $status = 'achieved (new)';
                        $this->notificationController->sendPushNotification($title, $body, $tokens);
//                        $this->createAchievementNotification($doctors, $user->name, $achievement->id);
                    } elseif (!$existingAchievement->pivot->achieved) {
                        $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);
                        $status = 'achieved (updated)';
                        $this->notificationController->sendPushNotification($title, $body, $tokens);
//                        $this->createAchievementNotification($doctors, $user->name, $achievement->id);
                    } else {
                        $status = 'already achieved';
                    }

                    // Log the achievement status
                    $achievementLog[] = [
                        'achievement_id' => $achievement->id,
                        'type' => $achievement->type,
                        'status' => $status,
                        'user_score' => $userScore,
                        'required_score' => $achievement->score,
                        'user_patient_count' => $userPatientCount,
                        'required_patient_count' => $achievement->score,
                    ];

                    Log::info('Achievement status updated', [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                        'status' => $status,
                    ]);
                }
            }

            // Log the result of the achievement checks and updates for each user
            Log::info('Achievements processed for user ' . $user->id, $achievementLog);
        }

        Log::info('All users processed for achievements.');
    }

    public function checkAndAssignAchievements(User $user)
    {
        // Retrieve the user's total score and patient count
        $userScore = $user->score->score ?? 0;
        $userPatientCount = $user->patients->count() ?? 0;

        // Log the initial score and patient count
        Log::debug('Initial user data', [
            'user_id' => $user->id,
            'user_score' => $userScore,
            'user_patient_count' => $userPatientCount,
        ]);

        // Fetch all achievements from the database
        $achievements = Achievement::all();

        // Initialize an array to store achievement updates for logging
        $achievementLog = [];

        // Loop through each achievement
        foreach ($achievements as $achievement) {
            // Check if the user already has this achievement
            $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();

            // Log the existing achievement status
            Log::debug('Checking achievement', [
                'achievement_id' => $achievement->id,
                'existing_achievement' => $existingAchievement ? 'exists' : 'does not exist',
            ]);

            // Determine whether the user qualifies for the achievement
            $qualifies = false;
            $body = ''; // Initialize body for notification
            switch ($achievement->type) {
                case 'score':
                    $qualifies = $userScore >= $achievement->score;
                    Log::debug('Score qualification check', [
                        'achievement_id' => $achievement->id,
                        'user_score' => $userScore,
                        'required_score' => $achievement->score,
                        'qualifies' => $qualifies,
                    ]);
                    break;

                case 'patient':
                    $qualifies = $userPatientCount >= $achievement->score;
                    $body = 'Dr. ' . $user->name . ' successfully added ' . $achievement->score . ' patients and earned a new achievement. Keep up the great work!';
                    Log::debug('Patient qualification check', [
                        'achievement_id' => $achievement->id,
                        'user_patient_count' => $userPatientCount,
                        'required_patient_count' => $achievement->score,
                        'qualifies' => $qualifies,
                    ]);
                    break;
            }

            // Get the IDs of doctors for notifications
            $doctors = User::role(['Admin', 'Tester'])->pluck('id');
            $title = 'Achievement Unlocked! 🎉';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)->pluck('token')->toArray();

            // Only assign or update if the user qualifies
            if ($qualifies) {
                // If the user doesn't have the achievement, assign it
                if (!$existingAchievement) {
                    $user->achievements()->attach($achievement->id, ['achieved' => true]);
                    $status = 'achieved (new)';
                    $this->notificationController->sendPushNotification($title, $body, $tokens);
                    $this->createAchievementNotification($doctors, $user->name, $achievement->id);
                }
                // If the user has the achievement but it wasn't achieved, update it
                elseif (!$existingAchievement->pivot->achieved) {
                    $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);
                    $status = 'achieved (updated)';
                    $this->notificationController->sendPushNotification($title, $body, $tokens);
                    $this->createAchievementNotification($doctors, $user->name, $achievement->id);
                } else {
                    $status = 'already achieved';
                }

                // Log the achievement status
                $achievementLog[] = [
                    'achievement_id' => $achievement->id,
                    'type' => $achievement->type,
                    'status' => $status,
                    'user_score' => $userScore,
                    'required_score' => $achievement->score,
                    'user_patient_count' => $userPatientCount,
                    'required_patient_count' => $achievement->score,
                ];

                // Log the action taken for the achievement
                Log::info('Achievement status updated', [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'status' => $status,
                ]);
            }
        }

        // Log the result of the achievement checks and updates
        Log::info('Achievements processed for user ' . $user->id, $achievementLog);

        // Return the log for debugging or further usage
        return $achievementLog;
    }

    /**
     * Create achievement notification for doctors
     *
     * @param array $doctors
     * @param string $doctorName
     * @param int $achievementId
     */
    private function createAchievementNotification(array $doctors, string $doctorName, int $achievementId)
    {
        foreach ($doctors as $doctorId) {
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
        // Load only the achievements where 'pivot.achieved' is 1
        $achievedAchievements = $user->achievements()->wherePivot('achieved', 1)->get();
    
        // Transform each achievement to ensure the necessary fields are strings
        $transformedAchievements = $achievedAchievements->map(function ($achievement) {
            return [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'type' => $achievement->type,
                'score' => (string) $achievement->score, // Convert score to string
                'image' => $achievement->image,
                'created_at' => $achievement->created_at,
                'updated_at' => $achievement->updated_at,
                'pivot' => [
                    'user_id' => (string) $achievement->pivot->user_id, // Convert user_id to string
                    'achievement_id' => (string) $achievement->pivot->achievement_id, // Convert achievement_id to string
                    'achieved' => (string) $achievement->pivot->achieved, // Convert achieved to string
                    'created_at' => $achievement->pivot->created_at,
                    'updated_at' => $achievement->pivot->updated_at,
                ]
            ];
        });
    
        return response()->json($transformedAchievements, 200);
    }
    

}
