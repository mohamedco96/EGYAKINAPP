<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\User;
use App\Modules\Patients\Models\Patients;
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
        // Total users/doctors
        $totalDoctors = User::count();

        // Total number of patients
        $totalPatients = Patients::count();

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
        // Find gender question ID
        $genderQuestion = Questions::where('question', 'LIKE', '%gender%')
            ->orWhere('question', 'LIKE', '%sex%')
            ->orWhere('question', 'LIKE', '%male%')
            ->orWhere('question', 'LIKE', '%female%')
            ->first();

        if (! $genderQuestion) {
            return ['male' => 0, 'female' => 0, 'other' => 0];
        }

        $genderAnswers = Answers::where('question_id', $genderQuestion->id)
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
            'other' => $genderAnswers->get('other', collect())->count(),
        ];
    }

    private function getDepartmentStats()
    {
        // Find department/specialty related questions
        $departmentQuestion = Questions::where('question', 'LIKE', '%department%')
            ->orWhere('question', 'LIKE', '%specialty%')
            ->orWhere('question', 'LIKE', '%ward%')
            ->first();

        if (! $departmentQuestion) {
            return [];
        }

        $departmentAnswers = Answers::where('question_id', $departmentQuestion->id)
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
        // Find DM (Diabetes Mellitus) related questions
        $dmQuestion = Questions::where('question', 'LIKE', '%DM%')
            ->orWhere('question', 'LIKE', '%diabetes%')
            ->orWhere('question', 'LIKE', '%diabetic%')
            ->first();

        if (! $dmQuestion) {
            return ['yes' => 0, 'no' => 0];
        }

        $dmAnswers = Answers::where('question_id', $dmQuestion->id)
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
        // Find HTN (Hypertension) related questions
        $htnQuestion = Questions::where('question', 'LIKE', '%HTN%')
            ->orWhere('question', 'LIKE', '%hypertension%')
            ->orWhere('question', 'LIKE', '%blood pressure%')
            ->first();

        if (! $htnQuestion) {
            return ['yes' => 0, 'no' => 0];
        }

        $htnAnswers = Answers::where('question_id', $htnQuestion->id)
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
        // Find provisional diagnosis related questions
        $diagnosisQuestion = Questions::where('question', 'LIKE', '%provisional%')
            ->orWhere('question', 'LIKE', '%diagnosis%')
            ->first();

        if (! $diagnosisQuestion) {
            return [];
        }

        $diagnosisAnswers = Answers::where('question_id', $diagnosisQuestion->id)
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
        // Find cause of AKI related questions
        $akiQuestion = Questions::where('question', 'LIKE', '%cause%')
            ->where('question', 'LIKE', '%AKI%')
            ->orWhere('question', 'LIKE', '%acute kidney%')
            ->first();

        if (! $akiQuestion) {
            return [];
        }

        $akiAnswers = Answers::where('question_id', $akiQuestion->id)
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
        // Find dialysis related questions
        $dialysisQuestion = Questions::where('question', 'LIKE', '%dialysis%')
            ->first();

        if (! $dialysisQuestion) {
            return 0;
        }

        $totalPatients = Patients::count();
        if ($totalPatients == 0) {
            return 0;
        }

        $dialysisCount = Answers::where('question_id', $dialysisQuestion->id)
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
        // Find outcome related questions
        $outcomeQuestion = Questions::where('question', 'LIKE', '%outcome%')
            ->orWhere('question', 'LIKE', '%result%')
            ->first();

        if (! $outcomeQuestion) {
            return [];
        }

        $outcomeAnswers = Answers::where('question_id', $outcomeQuestion->id)
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) {
                return $group->count();
            });

        return $outcomeAnswers->toArray();
    }

    private function getFinalStatusStats()
    {
        // Find final status related questions
        $statusQuestion = Questions::where('question', 'LIKE', '%final%')
            ->where('question', 'LIKE', '%status%')
            ->orWhere('question', 'LIKE', '%discharge%')
            ->first();

        if (! $statusQuestion) {
            return [];
        }

        $statusAnswers = Answers::where('question_id', $statusQuestion->id)
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) {
                return $group->count();
            });

        return $statusAnswers->toArray();
    }
}
