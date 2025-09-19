<?php

namespace App\Modules\Sections\Services;

use App\Models\Score;
use App\Models\ScoreHistory;
use App\Notifications\ReachingSpecificPoints;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScoringService
{
    private const FINAL_SUBMIT_SCORE = 4;

    private const NOTIFICATION_THRESHOLD = 50;

    /**
     * Process final submit scoring for a patient.
     */
    public function processFinalSubmitScoring(int $patientId): void
    {
        try {
            $doctorId = Auth::id();
            $user = Auth::user();

            if (! $doctorId || ! $user) {
                Log::warning('No authenticated user found for scoring');

                return;
            }

            // Fetch or create score record for the doctor
            $score = Score::firstOrNew(['doctor_id' => $doctorId]);
            $score->score += self::FINAL_SUBMIT_SCORE;
            $score->threshold += self::FINAL_SUBMIT_SCORE;

            $newThreshold = $score->threshold;

            // Send notification if score threshold reaches 50 or its multiples
            if ($newThreshold >= self::NOTIFICATION_THRESHOLD) {
                $user->notify(new ReachingSpecificPoints($score->score));
                $score->threshold = 0;
            }

            $score->save();

            // Log score history
            $this->logScoreHistory($doctorId, self::FINAL_SUBMIT_SCORE, 'Final Submit', $patientId);

            Log::info('Final submit scoring processed successfully', [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'score_added' => self::FINAL_SUBMIT_SCORE,
                'new_total_score' => $score->score,
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing final submit scoring', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log score history entry.
     */
    private function logScoreHistory(int $doctorId, int $scoreAmount, string $action, int $patientId): void
    {
        ScoreHistory::create([
            'doctor_id' => $doctorId,
            'score' => $scoreAmount,
            'action' => $action,
            'patient_id' => $patientId,
            'timestamp' => now(),
        ]);
    }
}
