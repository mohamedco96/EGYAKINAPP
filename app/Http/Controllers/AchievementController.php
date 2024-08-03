<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\Request;

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
        $userScore = $user->score->score;
        $achievements = Achievement::all();

        foreach ($achievements as $achievement) {
            $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();
            if (!$existingAchievement) {
                $achieved = $userScore >= $achievement->score;
                $user->achievements()->attach($achievement->id, ['achieved' => $achieved]);
            } elseif ($existingAchievement->pivot->achieved == false && $userScore >= $achievement->score) {
                $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);
            }
        }
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
