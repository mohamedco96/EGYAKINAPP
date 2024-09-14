<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AchievementController extends Controller
{
    public function createAchievement(Request $request)
    {
//        $request->validate([
//            'name' => 'required|string|max:255',
//            'description' => 'nullable|string',
//            'score' => 'required|integer',
//            'image' => 'nullable|image|max:2048'
//        ]);

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

            // Determine the type of achievement (score-based or patient-based)
            switch ($achievement->type) {
                case 'score':
                    // If achievement doesn't exist, check if the user qualifies and attach it
                    if (!$existingAchievement) {
                        $achieved = $userScore >= $achievement->score;
                        $user->achievements()->attach($achievement->id, ['achieved' => $achieved]);

                        // Log the newly attached achievement
                        $achievementLog[] = [
                            'achievement_id' => $achievement->id,
                            'type' => 'score',
                            'status' => $achieved,
                            'user_score' => $userScore,
                            'required_score' => $achievement->score
                        ];
                    }
                    // Update achievement status if the user now qualifies
                    elseif ($existingAchievement->pivot->achieved == false && $userScore >= $achievement->score) {
                        $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);

                        // Log the updated achievement
                        $achievementLog[] = [
                            'achievement_id' => $achievement->id,
                            'type' => 'score',
                            'status' => 'achieved (updated)',
                            'user_score' => $userScore,
                            'required_score' => $achievement->score
                        ];
                    }
                    break;

                case 'patient':
                    // If achievement doesn't exist, check if the user qualifies and attach it
                    if (!$existingAchievement) {
                        $achieved = $userPatientCount >= $achievement->score;
                        $user->achievements()->attach($achievement->id, ['achieved' => $achieved]);

                        // Log the newly attached achievement
                        $achievementLog[] = [
                            'achievement_id' => $achievement->id,
                            'type' => 'patient',
                            'status' => $achieved,
                            'user_patient_count' => $userPatientCount,
                            'required_patient_count' => $achievement->score
                        ];
                    }
                    // Update achievement status if the user now qualifies
                    elseif ($existingAchievement->pivot->achieved == false && $userPatientCount >= $achievement->score) {
                        $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);

                        // Log the updated achievement
                        $achievementLog[] = [
                            'achievement_id' => $achievement->id,
                            'type' => 'patient',
                            'status' => 'achieved (updated)',
                            'user_patient_count' => $userPatientCount,
                            'required_patient_count' => $achievement->score
                        ];
                    }
                    break;
            }
        }

        // Log the result of the achievement checks and updates
        Log::info('Achievements processed for user ' . $user->id, $achievementLog);

        // Return the log for debugging or further usage
        return $achievementLog;
    }


    public function listAchievements()
    {
        $achievements = Achievement::all();
        return response()->json($achievements, 200);
    }

    public function getUserAchievements(User $user)
    {
        $user->load('achievements');
        return response()->json($user->achievements, 200);
    }
}
