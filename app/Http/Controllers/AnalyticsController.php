<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use App\Modules\Questions\Models\Questions;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Get analytics data
        $analytics = $this->getAnalyticsData();

        return view('analytics', compact('analytics'));
    }

    private function getAnalyticsData()
    {
        // Total users/doctors - doctors are users with isSyndicateCardRequired = 'Verified'
        $totalDoctors = User::where('isSyndicateCardRequired', 'Verified')->count();
        $totalUsers = User::where('isSyndicateCardRequired', '!=', 'Verified')->orWhereNull('isSyndicateCardRequired')->count();

        // Total number of patients - only where hidden = false
        $totalPatients = Patients::where('hidden', false)->count();

        // Male and female patients count
        $genderStats = $this->getGenderStats();

        // Department statistics
        $departmentStats = $this->getDepartmentStats();

        // DM (Diabetes Mellitus) statistics
        $dmStats = $this->getDMStats();

        // HTN (Hypertension) statistics
        $htnStats = $this->getHTNStats();

        // Provisional diagnosis statistics
        $provisionalDiagnosisStats = $this->getProvisionalDiagnosisStats();

        // Cause of AKI statistics
        $causeOfAkiStats = $this->getCauseOfAkiStats();

        // Percentage of dialysis
        $dialysisPercentage = $this->getDialysisPercentage();

        // Patient with Outcome count
        $outcomeStats = $this->getOutcomeStats();

        // Patient Final status count
        $finalStatusStats = $this->getFinalStatusStats();

        return [
            'total_doctors' => $totalDoctors,
            'total_users' => $totalUsers,
            'total_patients' => $totalPatients,
            'gender_stats' => $genderStats,
            'department_stats' => $departmentStats,
            'dm_stats' => $dmStats,
            'htn_stats' => $htnStats,
            'provisional_diagnosis_stats' => $provisionalDiagnosisStats,
            'cause_of_aki_stats' => $causeOfAkiStats,
            'dialysis_percentage' => $dialysisPercentage,
            'outcome_stats' => $outcomeStats,
            'final_status_stats' => $finalStatusStats,
        ];
    }

    private function getGenderStats()
    {
        // Gender question ID: 8
        $genderQuestionId = 8;

        $genderAnswers = Answers::where('question_id', $genderQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                $answerValue = is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? strtolower($answer->answer[0]) : '') :
                    strtolower($answer->answer);

                if (str_contains($answerValue, 'male') && ! str_contains($answerValue, 'female')) {
                    return 'male';
                } elseif (str_contains($answerValue, 'female')) {
                    return 'female';
                } else {
                    return 'other';
                }
            });

        return [
            'male' => $genderAnswers->get('male', collect())->count(),
            'female' => $genderAnswers->get('female', collect())->count(),
        ];
    }

    private function getDepartmentStats()
    {
        // Department question ID: 168
        $departmentQuestionId = 168;

        $departmentAnswers = Answers::where('question_id', $departmentQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) {
                return $group->count();
            });

        return $departmentAnswers->toArray();
    }

    private function getDMStats()
    {
        // DM question ID: 16 - "Does the patient have DM?"
        $dmQuestionId = 16;

        $dmAnswers = Answers::where('question_id', $dmQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                $answerValue = is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? strtolower($answer->answer[0]) : '') :
                    strtolower($answer->answer);

                if (str_contains($answerValue, 'yes') || str_contains($answerValue, 'positive')) {
                    return 'yes';
                } else {
                    return 'no';
                }
            });

        return [
            'yes' => $dmAnswers->get('yes', collect())->count(),
            'no' => $dmAnswers->get('no', collect())->count(),
        ];
    }

    private function getHTNStats()
    {
        // HTN question ID: 18 - "Does the patient have HTN?"
        $htnQuestionId = 18;

        $htnAnswers = Answers::where('question_id', $htnQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                $answerValue = is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? strtolower($answer->answer[0]) : '') :
                    strtolower($answer->answer);

                if (str_contains($answerValue, 'yes') || str_contains($answerValue, 'positive')) {
                    return 'yes';
                } else {
                    return 'no';
                }
            });

        return [
            'yes' => $htnAnswers->get('yes', collect())->count(),
            'no' => $htnAnswers->get('no', collect())->count(),
        ];
    }

    private function getProvisionalDiagnosisStats()
    {
        // Provisional diagnosis question ID: 166 - "What is the Provisional diagnosis?"
        $diagnosisQuestionId = 166;

        $diagnosisAnswers = Answers::where('question_id', $diagnosisQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) {
                return $group->count();
            });

        return $diagnosisAnswers->toArray();
    }

    private function getCauseOfAkiStats()
    {
        // Cause of AKI question ID: 26 - "Cause of AKI"
        $akiQuestionId = 26;

        $akiAnswers = Answers::where('question_id', $akiQuestionId)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) {
                return $group->count();
            });

        return $akiAnswers->toArray();
    }

    private function getDialysisPercentage()
    {
        // Find dialysis related questions (keeping the search for now as no specific ID was provided)
        $dialysisQuestion = Questions::where('question', 'LIKE', '%dialysis%')
            ->first();

        if (! $dialysisQuestion) {
            return 0;
        }

        $totalPatients = Patients::where('hidden', false)->count();
        if ($totalPatients == 0) {
            return 0;
        }

        $dialysisCount = Answers::where('question_id', $dialysisQuestion->id)
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->where(function ($query) {
                $query->where('answer', 'LIKE', '%yes%')
                    ->orWhere('answer', 'LIKE', '%positive%')
                    ->orWhereRaw('JSON_EXTRACT(answer, "$[0]") LIKE "%yes%"')
                    ->orWhereRaw('JSON_EXTRACT(answer, "$[0]") LIKE "%positive%"');
            })
            ->count();

        return round(($dialysisCount / $totalPatients) * 100, 2);
    }

    private function getOutcomeStats()
    {
        $totalPatients = Patients::where('hidden', false)->count();

        // Patient count in patient_statuses with key outcome_status
        $outcomeStatuses = PatientStatus::where('key', 'outcome_status')
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get();

        $outcomeStats = $outcomeStatuses->groupBy('status')
            ->map(function ($group) use ($totalPatients) {
                $count = $group->count();
                $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 2) : 0;

                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        // Also get survivor/death breakdown
        $survivorDeathStats = $outcomeStatuses->groupBy(function ($status) {
            $statusValue = strtolower($status->status ?? '');
            if (str_contains($statusValue, 'death') || str_contains($statusValue, 'died') || str_contains($statusValue, 'dead')) {
                return 'Death';
            } elseif (str_contains($statusValue, 'survivor') || str_contains($statusValue, 'alive') || str_contains($statusValue, 'recovered')) {
                return 'Survivor';
            } else {
                return 'Other';
            }
        })
            ->map(function ($group) use ($totalPatients) {
                $count = $group->count();
                $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 2) : 0;

                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        return [
            'outcome_statuses' => $outcomeStats->toArray(),
            'survivor_death' => $survivorDeathStats->toArray(),
            'total_patients' => $totalPatients,
        ];
    }

    private function getFinalStatusStats()
    {
        $totalPatients = Patients::where('hidden', false)->count();

        // Patient count in patient_statuses with key submit_status
        $finalStatusStats = PatientStatus::where('key', 'submit_status')
            ->whereHas('patient', function ($query) {
                $query->where('hidden', false);
            })
            ->get()
            ->groupBy('status')
            ->map(function ($group) use ($totalPatients) {
                $count = $group->count();
                $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 2) : 0;

                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        return [
            'submit_statuses' => $finalStatusStats->toArray(),
            'total_patients' => $totalPatients,
        ];
    }
}
