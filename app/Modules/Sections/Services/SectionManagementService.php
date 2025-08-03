<?php

namespace App\Modules\Sections\Services;

use App\Models\Answers;
use App\Modules\Questions\Models\Questions;
use App\Models\SectionsInfo;
use App\Modules\Patients\Models\PatientStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SectionManagementService
{
    /**
     * Get questions and answers for a specific section and patient.
     *
     * @param int $sectionId
     * @param int $patientId
     * @return array
     */
    public function getQuestionsAndAnswers(int $sectionId, int $patientId): array
    {
        // Check if the section exists
        $sectionExists = Questions::where('section_id', $sectionId)->exists();
        if (!$sectionExists) {
            return [];
        }

        // Fetch questions for the specified section
        $questions = Questions::where('section_id', $sectionId)
            ->orderBy('sort')
            ->get();

        // Fetch all answers for the patient related to these questions
        $answers = Answers::where('patient_id', $patientId)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();

        $data = [];

        foreach ($questions as $question) {
            // Skip questions flagged with 'skip'
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");
                continue;
            }

            // Find answer for this question
            $answer = $answers->where('question_id', $question->id)->first();

            // Skip hidden questions with no answer
            if ($question->hidden && !$answer) {
                Log::info("Hidden question with ID {$question->id} skipped due to no answer.");
                continue;
            }

            // Prepare question data
            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'hidden' => $question->hidden,
                'updated_at' => $question->updated_at,
            ];

            // Handle different question types
            $questionData['answer'] = $this->formatAnswerByType($question, $answers);

            $data[] = $questionData;
        }

        return $data;
    }

    /**
     * Get submitter information for section 8.
     *
     * @param int $patientId
     * @return array
     */
    public function getSubmitterInfo(int $patientId): array
    {
        $submitter = PatientStatus::select('id', 'doctor_id')
            ->where('patient_id', $patientId)
            ->where('key', 'outcome_status')
            ->with(['doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
            }])
            ->first();

        if ($submitter && $submitter->doctor) {
            $doctor = $submitter->doctor;
            return [
                'id' => (string) optional($submitter)->doctor_id,
                'name' => (optional($doctor)->name && optional($doctor)->lname)
                    ? optional($doctor)->name . ' ' . optional($doctor)->lname
                    : null,
                'image' => optional($doctor)->image,
                'syndicateCard' => optional($doctor)->isSyndicateCardRequired,
            ];
        }

        return [
            'name' => null,
            'image' => null,
        ];
    }

    /**
     * Get sections data for a patient.
     *
     * @param int $patientId
     * @return array
     */
    public function getSectionsData(int $patientId): array
    {
        // Fetch sections data related to the patient
        $sections = PatientStatus::select('key', 'status', 'updated_at')
            ->where('patient_id', $patientId)
            ->where('key', 'LIKE', 'section_%')
            ->get();

        // Fetch section information from SectionsInfo model
        $sectionInfos = SectionsInfo::where('id', '<>', 8)->get();

        $data = [];
        foreach ($sectionInfos as $sectionInfo) {
            $sectionId = $sectionInfo->id;
            $sectionName = $sectionInfo->section_name;

            // Find section data in $sections collection
            $sectionData = $sections->firstWhere('key', 'section_' . $sectionId);

            // Initialize variables for section status and updated_at value
            $sectionStatus = false;
            $updatedAtValue = null;

            // Populate section status and updated_at if section data exists
            if ($sectionData) {
                $sectionStatus = $sectionData->status;
                $updatedAtValue = $sectionData->updated_at;
            }

            $data[] = [
                'section_id' => $sectionId,
                'section_status' => $sectionStatus,
                'updated_at' => $updatedAtValue,
                'section_name' => $sectionName,
            ];
        }

        return $data;
    }

    /**
     * Get patient basic data for sections view.
     *
     * @param int $patientId
     * @return array
     */
    public function getPatientBasicData(int $patientId): array
    {
        // Get submit status
        $submitStatus = PatientStatus::where('patient_id', $patientId)
            ->where('key', 'submit_status')
            ->value('status');

        // Get patient name
        $patientName = Answers::where('patient_id', $patientId)
            ->where('question_id', '1')
            ->value('answer');

        // Get doctor ID
        $doctorId = \App\Modules\Patients\Models\Patients::where('id', $patientId)
            ->value('doctor_id');

        return [
            'submit_status' => $submitStatus,
            'patient_name' => $patientName,
            'doctor_id' => (string) $doctorId,
        ];
    }

    /**
     * Get patient data needed for GFR calculations.
     *
     * @param int $patientId
     * @return array
     */
    public function getPatientGfrData(int $patientId): array
    {
        $questionIds = [
            'gender' => '8',
            'age' => '7',
            'height' => '140',
            'weight' => '141',
            'current_creatinine' => '71',
            'basal_creatinine' => '72',
            'creatinine_on_discharge' => '80',
            'race' => '149',
        ];

        // Get all answers in a single query
        $answers = Answers::where('patient_id', $patientId)
            ->whereIn('question_id', array_values($questionIds))
            ->pluck('answer', 'question_id')
            ->toArray();

        $data = [];
        foreach ($questionIds as $key => $questionId) {
            $data[$key] = $answers[$questionId] ?? null;
        }

        return $data;
    }

    /**
     * Format answer based on question type.
     *
     * @param object $question
     * @param \Illuminate\Support\Collection $answers
     * @return mixed
     */
    private function formatAnswerByType($question, $answers)
    {
        $questionAnswers = $answers->where('question_id', $question->id);

        switch ($question->type) {
            case 'select':
                $result = [
                    'answers' => null,
                    'other_field' => null,
                ];

                foreach ($questionAnswers as $ans) {
                    if ($ans->type !== 'other') {
                        $result['answers'] = $ans->answer;
                    }
                    if ($ans->type === 'other') {
                        $result['other_field'] = $ans->answer;
                    }
                }

                return $result;

            case 'multiple':
                $result = [
                    'answers' => [],
                    'other_field' => null,
                ];

                foreach ($questionAnswers as $ans) {
                    if ($ans->type !== 'other') {
                        $result['answers'][] = $ans->answer;
                    }
                    if ($ans->type === 'other') {
                        $result['other_field'] = $ans->answer;
                    }
                }

                return $result;

            case 'files':
                $answer = $questionAnswers->first();
                if (!$answer) {
                    return [];
                }

                $filePaths = json_decode($answer->answer);
                $result = [];

                if (is_array($filePaths)) {
                    foreach ($filePaths as $filePath) {
                        $result[] = Storage::disk('public')->url($filePath);
                    }
                }

                return $result;

            default:
                $answer = $questionAnswers->first();
                return $answer ? $answer->answer : null;
        }
    }
}
