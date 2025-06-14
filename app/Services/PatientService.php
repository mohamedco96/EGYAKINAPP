<?php

namespace App\Services;

use App\Models\Patients;
use App\Models\Answers;
use App\Models\PatientStatus;
use App\Models\Questions;
use App\Models\User;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\AppNotification;
use App\Models\FcmToken;
use App\Models\Consultation;
use App\Models\ConsultationDoctor;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class PatientService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check if patient exists
     */
    public function patientExists(int $patientId): bool
    {
        return Patients::where('id', $patientId)->exists();
    }

    /**
     * Create a new patient with answers and status
     */
    public function createPatient(array $requestData): array
    {
        return DB::transaction(function () use ($requestData) {
            $doctorId = Auth::id();
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');
            
            $questionSectionIds = Questions::pluck('section_id', 'id')->toArray();
            
            $patient = Patients::create([
                'doctor_id' => $doctorId,
                'hidden' => $isAdminOrTester,
            ]);

            $answersToSave = [];
            $this->processAnswers($requestData, $answersToSave, $doctorId, $patient->id, $questionSectionIds);
            
            Answers::insert($answersToSave);
            
            $this->createPatientStatuses($doctorId, $patient->id, $questionSectionIds);
            
            $patientName = $this->extractPatientName($answersToSave, $patient->id);
            
            $this->sendNewPatientNotifications($user, $patient->id, $patientName);
            
            Log::info('New patient created', ['doctor_id' => $doctorId, 'patient_id' => $patient->id]);
            
            return [
                'value' => true,
                'doctor_id' => $doctorId,
                'id' => $patient->id,
                'name' => $patientName,
                'submit_status' => false,
                'message' => 'Patient Created Successfully',
            ];
        });
    }

    /**
     * Update patient section with answers
     */
    public function updatePatientSection(array $requestData, int $sectionId, int $patientId): array
    {
        return DB::transaction(function () use ($requestData, $sectionId, $patientId) {
            $doctorId = Auth::id();
            $questionSectionIds = Questions::pluck('section_id', 'id')->toArray();
            
            $patientSectionStatus = PatientStatus::where('patient_id', $patientId)
                ->where('key', 'section_' . $sectionId)
                ->first();

            if ($patientSectionStatus) {
                $this->updateExistingSection($requestData, $patientId, $doctorId, $sectionId, $questionSectionIds);
                $patientSectionStatus->touch();
            } else {
                $this->createNewSection($requestData, $patientId, $doctorId, $sectionId, $questionSectionIds);
            }

            $this->handleOutcomeStatusUpdate($patientId, $sectionId, $doctorId);
            
            Log::info('Section_' . $sectionId . ' updated successfully', [
                'doctor_id' => $doctorId, 
                'patient_id' => $patientId
            ]);
            
            return [
                'value' => true,
                'message' => 'Section updated successfully.',
            ];
        });
    }

    /**
     * Delete patient and all related data
     */
    public function deletePatient(int $patientId): array
    {
        return DB::transaction(function () use ($patientId) {
            $patient = Patients::findOrFail($patientId);
            
            // Delete related consultation data
            ConsultationDoctor::whereIn('consultation_id', function ($query) use ($patientId) {
                $query->select('id')
                    ->from('consultations')
                    ->where('patient_id', $patientId);
            })->delete();
            
            Consultation::where('patient_id', $patientId)->delete();
            
            // Handle score adjustments
            $this->adjustDoctorScores($patientId);
            
            $patient->delete();
            
            Log::info('Patient deleted successfully', ['patient_id' => $patientId]);
            
            return [
                'value' => true,
                'message' => 'Patient and related data deleted successfully',
            ];
        });
    }

    /**
     * Transform patient data for API response
     */
    public function transformPatientData($patient): array
    {
        $submitStatus = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
        $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;
        $outcomeSubmitterDoctorId = optional($patient->status->where('key', 'outcome_status')->first())->doctor_id;

        return [
            'id' => $patient->id,
            'doctor_id' => (int)$patient->doctor_id,
            'name' => optional($patient->answers->where('question_id', 1)->first())->answer,
            'hospital' => optional($patient->answers->where('question_id', 2)->first())->answer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
            ],
            'submitter' => [
                'submitter_id' => $outcomeSubmitterDoctorId ? (int)$outcomeSubmitterDoctorId : null,
                'submitter_fname' => optional($patient->doctor)->name,
                'submitter_lname' => optional($patient->doctor)->lname,
                'submitter_SyndicateCard' => optional($patient->doctor)->isSyndicateCardRequired
            ]
        ];
    }

    /**
     * Process request data to prepare answers for saving
     */
    private function processAnswers(array $requestData, array &$answersToSave, int $doctorId, int $patientId, array $questionSectionIds): void
    {
        foreach ($requestData as $key => $value) {
            if (preg_match('/^\d+$/', $key)) {
                $questionId = (int)$key;
                $sectionId = $questionSectionIds[$questionId] ?? null;

                if (isset($value['answers'])) {
                    $this->prepareAnswersToSave($answersToSave, $doctorId, $questionId, $value['answers'], $patientId, false, $sectionId);
                    $this->prepareAnswersToSave($answersToSave, $doctorId, $questionId, $value['other_field'] ?? null, $patientId, true, $sectionId);
                } elseif (isset($questionSectionIds[$questionId])) {
                    $this->prepareAnswersToSave($answersToSave, $doctorId, $questionId, $value, $patientId, false, $sectionId);
                }
            }
        }
    }

    /**
     * Prepare data for batch insert of answers
     */
    private function prepareAnswersToSave(array &$answersToSave, int $doctorId, int $questionId, $answer, int $patientId, bool $isOtherField, ?int $sectionId): void
    {
        if ($answer === null) {
            return;
        }

        $answerText = is_array($answer) ? json_encode($answer) : '"' . addslashes($answer) . '"';

        $answersToSave[] = [
            'doctor_id' => $doctorId,
            'section_id' => $sectionId,
            'question_id' => $questionId,
            'patient_id' => $patientId,
            'answer' => $answerText,
            'type' => $isOtherField ? 'other' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create initial patient status records
     */
    private function createPatientStatuses(int $doctorId, int $patientId, array $questionSectionIds): void
    {
        $now = Carbon::now();
        $patientStatusesToCreate = [
            [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'key' => 'section_' . ($questionSectionIds[1] ?? null),
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'key' => 'submit_status',
                'status' => false,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'key' => 'outcome_status',
                'status' => false,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];

        PatientStatus::insert($patientStatusesToCreate);
    }

    /**
     * Extract patient name from answers array
     */
    private function extractPatientName(array $answersToSave, int $patientId): ?string
    {
        foreach ($answersToSave as $answer) {
            if ($answer['patient_id'] === $patientId && $answer['question_id'] === 1) {
                return stripslashes(trim($answer['answer'], '"'));
            }
        }
        return null;
    }

    /**
     * Send notifications about new patient creation
     */
    private function sendNewPatientNotifications(User $user, int $patientId, ?string $patientName): void
    {
        $doctors = User::where('id', '!=', Auth::id())
            ->where('isSyndicateCardRequired', 'Verified')
            ->pluck('id');

        foreach ($doctors as $doctorId) {
            AppNotification::create([
                'doctor_id' => $doctorId,
                'type' => 'New Patient',
                'content' => 'New Patient was created',
                'patient_id' => $patientId
            ]);
        }

        $title = 'New Patient was created 📣';
        $body = 'Dr. ' . ucfirst($user->name) . ' added a new patient named ' . $patientName;
        $tokens = FcmToken::whereIn('doctor_id', $doctors)->pluck('token')->toArray();

        $this->notificationService->sendPushNotification($title, $body, $tokens);
    }

    /**
     * Update existing section answers
     */
    private function updateExistingSection(array $requestData, int $patientId, int $doctorId, int $sectionId, array $questionSectionIds): void
    {
        foreach ($requestData as $key => $value) {
            if (preg_match('/^\d+$/', $key)) {
                $questionId = (int)$key;
                $questionExists = Answers::where('patient_id', $patientId)
                    ->where('question_id', $questionId)
                    ->exists();

                if ($questionExists) {
                    $this->updateAnswerLogic($questionId, $value, $patientId, $sectionId);
                } else {
                    $this->saveAnswerLogic($doctorId, $questionId, $value, $patientId, $sectionId);
                }
            }
        }
    }

    /**
     * Create new section with answers
     */
    private function createNewSection(array $requestData, int $patientId, int $doctorId, int $sectionId, array $questionSectionIds): void
    {
        foreach ($requestData as $key => $value) {
            if (preg_match('/^\d+$/', $key)) {
                $questionId = (int)$key;
                $questionExists = Answers::where('patient_id', $patientId)
                    ->where('question_id', $questionId)
                    ->exists();

                if ($questionExists) {
                    $this->updateAnswerLogic($questionId, $value, $patientId, $sectionId);
                } else {
                    $this->saveAnswerLogic($doctorId, $questionId, $value, $patientId, $sectionId);
                }
            }
        }

        PatientStatus::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'key' => 'section_' . $sectionId,
            'status' => true
        ]);
    }

    /**
     * Handle outcome status updates and scoring
     */
    private function handleOutcomeStatusUpdate(int $patientId, int $sectionId, int $doctorId): void
    {
        if ($sectionId !== 8) {
            return;
        }

        $patientOutcomeStatus = PatientStatus::where('patient_id', $patientId)
            ->where('key', 'outcome_status')
            ->first();

        if ($patientOutcomeStatus && $patientOutcomeStatus->status === false) {
            $patientOutcomeStatus->update([
                'status' => true,
                'doctor_id' => $doctorId
            ]);
        } elseif (!$patientOutcomeStatus) {
            PatientStatus::create([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'key' => 'outcome_status',
                'status' => true
            ]);

            $this->updateDoctorScore($doctorId, $patientId);
        }
    }

    /**
     * Update doctor score for adding outcome
     */
    private function updateDoctorScore(int $doctorId, int $patientId): void
    {
        $incrementAmount = 1;
        $action = 'Add Outcome';

        $score = Score::firstOrNew(['doctor_id' => $doctorId]);
        $score->score += $incrementAmount;
        $score->threshold += $incrementAmount;
        $newThreshold = $score->threshold;

        if ($newThreshold >= 50) {
            $user = User::find($doctorId);
            // Notification logic would go here
            $score->threshold = 0;
        }

        $score->save();

        ScoreHistory::create([
            'doctor_id' => $doctorId,
            'score' => $incrementAmount,
            'action' => $action,
            'patient_id' => $patientId,
            'timestamp' => now(),
        ]);
    }

    /**
     * Adjust doctor scores when deleting patient
     */
    private function adjustDoctorScores(int $patientId): void
    {
        $scoreHistories = ScoreHistory::where('patient_id', $patientId)->get();
        $doctorDecrementAmounts = $scoreHistories->groupBy('doctor_id')->map(function ($histories) {
            return $histories->sum('score');
        });

        foreach ($doctorDecrementAmounts as $doctorId => $decrementAmount) {
            if ($decrementAmount > 0) {
                $score = Score::firstOrNew(['doctor_id' => $doctorId]);
                $score->score = max(0, $score->score - $decrementAmount);
                $score->threshold = max(0, $score->threshold - $decrementAmount);
                $score->save();
            }
        }
    }

    /**
     * Logic for updating answers
     */
    private function updateAnswerLogic(int $questionId, $value, int $patientId, int $sectionId): void
    {
        if ($this->isFileTypeQuestion($questionId)) {
            $fileUrls = $this->handleFileUploads($value);
            $this->updateAnswer($questionId, json_encode($fileUrls), $patientId, false, $sectionId);
        } else {
            if (isset($value['answers'])) {
                $this->updateAnswer($questionId, json_encode($value['answers']), $patientId, false, $sectionId);
                $this->updateAnswer($questionId, json_encode($value['other_field'] ?? null), $patientId, true, $sectionId);
            } else {
                $this->updateAnswer($questionId, json_encode($value), $patientId, false, $sectionId);
            }
        }
    }

    /**
     * Logic for saving new answers
     */
    private function saveAnswerLogic(int $doctorId, int $questionId, $value, int $patientId, int $sectionId): void
    {
        if ($this->isFileTypeQuestion($questionId)) {
            $fileUrls = $this->handleFileUploads($value);
            $this->saveAnswer($doctorId, $questionId, json_encode($fileUrls), $patientId, false, $sectionId);
        } else {
            if (isset($value['answers'])) {
                $this->saveAnswer($doctorId, $questionId, $value['answers'], $patientId, false, $sectionId);
                $this->saveAnswer($doctorId, $questionId, $value['other_field'] ?? null, $patientId, true, $sectionId);
            } else {
                $this->saveAnswer($doctorId, $questionId, $value, $patientId, false, $sectionId);
            }
        }
    }

    /**
     * Check if question is file type
     */
    private function isFileTypeQuestion(int $questionId): bool
    {
        $question = Questions::find($questionId);
        return $question && $question->type === 'files';
    }

    /**
     * Handle file uploads
     */
    private function handleFileUploads(array $files): array
    {
        // This method would contain the file upload logic
        // Implementation would be moved from the controller
        return [];
    }

    /**
     * Save answer to database
     */
    private function saveAnswer(int $doctorId, int $questionId, $answerText, int $patientId, bool $isOtherField = false, ?int $sectionId = null): void
    {
        Patients::where('id', $patientId)->update(['updated_at' => now()]);

        $question = Questions::find($questionId);
        if ($question && $question->type === 'files') {
            $answerText = is_array($answerText) ? json_encode($answerText, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $answerText;
        }

        Answers::create([
            'doctor_id' => $doctorId,
            'section_id' => $sectionId,
            'question_id' => $questionId,
            'patient_id' => $patientId,
            'answer' => $answerText,
            'type' => $isOtherField ? 'other' : null,
        ]);
    }

    /**
     * Update existing answer
     */
    private function updateAnswer(int $questionId, $answerText, int $patientId, bool $isOtherField = false, ?int $sectionId = null): void
    {
        $question = Questions::find($questionId);
        if ($question && $question->type === 'files') {
            $answerText = json_encode($answerText);
        }

        Patients::where('id', $patientId)->update(['updated_at' => now()]);

        if ($isOtherField) {
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->whereNotNull('type')
                ->update([
                    'answer' => $answerText,
                    'type' => 'other',
                ]);
        } else {
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->whereNull('type')
                ->update([
                    'answer' => $answerText,
                    'type' => null,
                ]);
        }
    }
}
