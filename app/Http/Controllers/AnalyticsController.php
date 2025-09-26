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

        // Get dark mode parameter (default is false for white mode)
        $isDark = request()->boolean('dark', false);

        // Get locale parameter (default is 'en')
        $locale = request()->get('lang', app()->getLocale());

        // Set the application locale
        app()->setLocale($locale);

        return view('analytics', compact('analytics', 'isDark', 'locale'));
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

        $maleCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $genderQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%male%"')
                    ->whereRaw('LOWER(answers.answer) NOT LIKE "%female%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%male%"')
                    ->whereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) NOT LIKE "%female%"');
            })
            ->count();

        $femaleCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $genderQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%female%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%female%"');
            })
            ->count();

        return [
            'male' => $maleCount,
            'female' => $femaleCount,
        ];
    }

    private function getDepartmentStats()
    {
        // Department question ID: 168
        $departmentQuestionId = 168;

        $departmentStats = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $departmentQuestionId)
            ->where('patients.hidden', false)
            ->selectRaw('
                CASE 
                    WHEN JSON_EXTRACT(answers.answer, "$[0]") IS NOT NULL 
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) != ""
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) IS NOT NULL
                    THEN TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]"))
                    WHEN answers.answer IS NOT NULL 
                        AND TRIM(answers.answer) != ""
                        AND TRIM(answers.answer) IS NOT NULL
                    THEN answers.answer
                    ELSE "Unknown"
                END as department_name,
                COUNT(*) as count
            ')
            ->groupBy('department_name')
            ->havingRaw('department_name != ""')
            ->pluck('count', 'department_name');

        return $departmentStats->toArray();
    }

    private function getDMStats()
    {
        // DM question ID: 16 - "Does the patient have DM?"
        $dmQuestionId = 16;

        $yesCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $dmQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%yes%"')
                    ->orWhereRaw('LOWER(answers.answer) LIKE "%positive%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%yes%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%positive%"');
            })
            ->count();

        $noCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $dmQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%no%"')
                    ->orWhereRaw('LOWER(answers.answer) LIKE "%negative%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%no%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%negative%"');
            })
            ->count();

        return [
            'yes' => $yesCount,
            'no' => $noCount,
        ];
    }

    private function getHTNStats()
    {
        // HTN question ID: 18 - "Does the patient have HTN?"
        $htnQuestionId = 18;

        $yesCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $htnQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%yes%"')
                    ->orWhereRaw('LOWER(answers.answer) LIKE "%positive%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%yes%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%positive%"');
            })
            ->count();

        $noCount = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $htnQuestionId)
            ->where('patients.hidden', false)
            ->where(function ($query) {
                $query->whereRaw('LOWER(answers.answer) LIKE "%no%"')
                    ->orWhereRaw('LOWER(answers.answer) LIKE "%negative%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%no%"')
                    ->orWhereRaw('LOWER(JSON_EXTRACT(answers.answer, "$[0]")) LIKE "%negative%"');
            })
            ->count();

        return [
            'yes' => $yesCount,
            'no' => $noCount,
        ];
    }

    private function getProvisionalDiagnosisStats()
    {
        // Provisional diagnosis question ID: 166 - "What is the Provisional diagnosis?"
        $diagnosisQuestionId = 166;

        $diagnosisStats = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $diagnosisQuestionId)
            ->where('patients.hidden', false)
            ->selectRaw('
                CASE 
                    WHEN JSON_EXTRACT(answers.answer, "$[0]") IS NOT NULL 
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) != ""
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) IS NOT NULL
                    THEN TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]"))
                    WHEN answers.answer IS NOT NULL 
                        AND TRIM(answers.answer) != ""
                        AND TRIM(answers.answer) IS NOT NULL
                    THEN answers.answer
                    ELSE "Unknown"
                END as diagnosis_name,
                COUNT(*) as count
            ')
            ->groupBy('diagnosis_name')
            ->havingRaw('diagnosis_name != ""')
            ->pluck('count', 'diagnosis_name');

        return $diagnosisStats->toArray();
    }

    private function getCauseOfAkiStats()
    {
        // Cause of AKI question ID: 26 - "Cause of AKI"
        $akiQuestionId = 26;

        $akiStats = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $akiQuestionId)
            ->where('patients.hidden', false)
            ->selectRaw('
                CASE 
                    WHEN JSON_EXTRACT(answers.answer, "$[0]") IS NOT NULL 
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) != ""
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) IS NOT NULL
                    THEN TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]"))
                    WHEN answers.answer IS NOT NULL 
                        AND TRIM(answers.answer) != ""
                        AND TRIM(answers.answer) IS NOT NULL
                    THEN answers.answer
                    ELSE "Unknown"
                END as aki_cause,
                COUNT(*) as count
            ')
            ->groupBy('aki_cause')
            ->havingRaw('aki_cause != ""')
            ->pluck('count', 'aki_cause');

        return $akiStats->toArray();
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

        $outcomeStats = Answers::join('patients', 'answers.patient_id', '=', 'patients.id')
            ->where('answers.question_id', $outcomeQuestionId)
            ->where('patients.hidden', false)
            ->selectRaw('
                CASE 
                    WHEN JSON_EXTRACT(answers.answer, "$[0]") IS NOT NULL 
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) != ""
                        AND TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]")) IS NOT NULL
                    THEN TRIM(BOTH \'"\' FROM JSON_EXTRACT(answers.answer, "$[0]"))
                    WHEN answers.answer IS NOT NULL 
                        AND TRIM(answers.answer) != ""
                        AND TRIM(answers.answer) IS NOT NULL
                    THEN answers.answer
                    ELSE "Unknown"
                END as outcome_value,
                COUNT(*) as count
            ')
            ->groupBy('outcome_value')
            ->havingRaw('outcome_value != ""')
            ->get()
            ->mapWithKeys(function ($item) use ($totalPatients) {
                $percentage = $totalPatients > 0 ? round(($item->count / $totalPatients) * 100, 2) : 0;

                return [
                    $item->outcome_value => [
                        'count' => $item->count,
                        'percentage' => $percentage,
                    ],
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
            'outcome_values' => $outcomeStats->toArray(),
            'outcome_status_count' => $outcomeStatusCount,
            'submit_status_count' => $submitStatusCount,
            'total_patients' => $totalPatients,
        ];
    }
}
