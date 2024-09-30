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

    public function checkAndAssignAchievements(User $user)
    {
        // Retrieve the user's total score and patient count
        $userScore = $user->score->score ?? 0;
        $userPatientCount = $user->patients->count() ?? 0;

        // Fetch all achievements from the database
        $achievements = Achievement::all();

        // Initialize an array to store achievement updates for logging
        $achievementLog = [];

        // Loop through each achievement
        foreach ($achievements as $achievement) {
            // Check if the user already has this achievement
            $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();

            // Determine whether the user qualifies for the achievement
            $qualifies = false;
            switch ($achievement->type) {
                case 'score':
                    $qualifies = $userScore >= $achievement->score;
                    break;

                case 'patient':
                    $qualifies = $userPatientCount >= $achievement->score;
                    $body = 'Dr. '. $user->name .' successfully added ' . $achievement->score . ' patients and earned a new achievement. Keep up the great work!';
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

        return response()->json($achievedAchievements, 200);
    }

}
