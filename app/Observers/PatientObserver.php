<?php

namespace App\Observers;

use App\Models\User;
use App\Modules\Achievements\Services\AchievementService;
use App\Modules\Patients\Models\Patients;
use Illuminate\Support\Facades\Log;

class PatientObserver
{
    protected AchievementService $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Handle the Patient "created" event.
     */
    public function created(Patients $patient): void
    {
        $this->checkAchievements($patient);
    }

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Patients $patient): void
    {
        $this->checkAchievements($patient);
    }

    /**
     * Check achievements for the doctor when their patient count changes
     */
    private function checkAchievements(Patients $patient): void
    {
        try {
            $user = User::find($patient->doctor_id);

            if ($user) {
                Log::info('Patient count changed, checking achievements', [
                    'user_id' => $user->id,
                    'patient_id' => $patient->id,
                ]);

                $this->achievementService->checkAndAssignAchievementsForUser($user);
            }
        } catch (\Exception $e) {
            Log::error('Error checking achievements after patient change', [
                'patient_id' => $patient->id,
                'doctor_id' => $patient->doctor_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
