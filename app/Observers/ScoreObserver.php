<?php

namespace App\Observers;

use App\Models\Score;
use App\Models\User;
use App\Modules\Achievements\Services\AchievementService;
use Illuminate\Support\Facades\Log;

class ScoreObserver
{
    protected AchievementService $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Handle the Score "created" event.
     */
    public function created(Score $score): void
    {
        $this->checkAchievements($score);
    }

    /**
     * Handle the Score "updated" event.
     */
    public function updated(Score $score): void
    {
        // Only check achievements if the score value actually changed
        if ($score->isDirty('score')) {
            $this->checkAchievements($score);
        }
    }

    /**
     * Check achievements for the user when their score changes
     */
    private function checkAchievements(Score $score): void
    {
        try {
            $user = User::find($score->doctor_id);

            if ($user) {
                Log::info('Score changed, checking achievements', [
                    'user_id' => $user->id,
                    'new_score' => $score->score,
                ]);

                $this->achievementService->checkAndAssignAchievementsForUser($user);
            }
        } catch (\Exception $e) {
            Log::error('Error checking achievements after score update', [
                'score_id' => $score->id,
                'doctor_id' => $score->doctor_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
