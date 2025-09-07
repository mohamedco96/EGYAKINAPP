<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\User;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;

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
        ];
    }

    private function getGenderStats()
    {
        // Gender question ID: 8
        $genderQuestionId = 8;

        $genderAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $genderQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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

        $departmentAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $departmentQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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

        $dmAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $dmQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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

        $htnAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $htnQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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

        $diagnosisAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $diagnosisQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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

        $akiAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $akiQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
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
        // Dialysis question ID: 86 - "Did the patient receive dialysis?"
        $dialysisQuestionId = 86;

        $totalPatients = Patients::where('hidden', false)->count();
        if ($totalPatients == 0) {
            return 0;
        }

        $dialysisCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $dialysisQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->where('answers.answer', 'LIKE', '%yes%')
                    ->orWhere('answers.answer', 'LIKE', '%positive%')
                    ->orWhereRaw('JSON_EXTRACT(answers.answer, "$[0]") LIKE "%yes%"')
                    ->orWhereRaw('JSON_EXTRACT(answers.answer, "$[0]") LIKE "%positive%"');
            })
            ->count();

        return round(($dialysisCount / $totalPatients) * 100, 2);
    }

    private function getOutcomeStats()
    {
        $totalPatients = Patients::where('hidden', false)->count();

        // Outcome values from question ID 79: "Outcome of the patient"
        $outcomeQuestionId = 79;

        $outcomeAnswers = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $outcomeQuestionId)
            ->where('patients.hidden', false)
            ->select('answers.*')
            ->get()
            ->groupBy(function ($answer) {
                return is_array($answer->answer) ?
                    (isset($answer->answer[0]) ? $answer->answer[0] : 'Unknown') :
                    ($answer->answer ?: 'Unknown');
            })
            ->map(function ($group) use ($totalPatients) {
                $count = $group->count();
                $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100, 2) : 0;

                return [
                    'count' => $count,
                    'percentage' => $percentage,
                ];
            });

        // Patient count in patient_statuses with status = true (only count true status)
        $outcomeStatusCount = PatientStatus::join('patients', 'patient_statuses.patient_id', '=', 'patients.id')
            ->where('patient_statuses.key', 'outcome_status')
            ->where('patient_statuses.status', true)
            ->where('patients.hidden', false)
            ->count();

        $submitStatusCount = PatientStatus::join('patients', 'patient_statuses.patient_id', '=', 'patients.id')
            ->where('patient_statuses.key', 'submit_status')
            ->where('patient_statuses.status', true)
            ->where('patients.hidden', false)
            ->count();

        return [
            'outcome_values' => $outcomeAnswers->toArray(),
            'outcome_status_count' => $outcomeStatusCount,
            'submit_status_count' => $submitStatusCount,
            'total_patients' => $totalPatients,
        ];
    }
}
