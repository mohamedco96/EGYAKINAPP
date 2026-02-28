<?php

namespace App\Modules\Chat\Services;

use App\Models\SectionsInfo;
use App\Modules\Chat\Models\AIConsultation;
use App\Modules\Chat\Models\DoctorMonthlyTrial;
use App\Modules\Patients\Models\Patients;
use App\Modules\Questions\Models\Questions;
use App\Services\ChatGPTService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatService
{
    protected $chatGPTService;

    public function __construct(ChatGPTService $chatGPTService)
    {
        $this->chatGPTService = $chatGPTService;
    }

    /**
     * Check if the current user can modify consultations for a patient.
     * Only admins and patient owners can create consultations.
     */
    private function canModifyConsultations(Patients $patient): bool
    {
        $user = Auth::user();

        // Check if user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is the patient owner
        if ($patient->doctor_id === Auth::id()) {
            return true;
        }

        return false;
    }

    /**
     * Send consultation request for a patient
     */
    public function sendConsultation(int $patientId): array
    {
        try {
            // Retrieve the patient from the database with related data
            $patient = Patients::with(['doctor', 'status', 'answers'])->findOrFail($patientId);

            // Check if the patient exists
            if (! $patient) {
                Log::error('Patient not found', [
                    'patient_id' => $patientId,
                ]);

                return [
                    'success' => false,
                    'data' => [
                        'value' => false,
                        'message' => 'Patient not found',
                    ],
                    'status_code' => 404,
                ];
            }

            // Check if user can modify consultations (admin or patient owner)
            if (! $this->canModifyConsultations($patient)) {
                Log::warning('Unauthorized AI consultation attempt', [
                    'doctor_id' => Auth::id(),
                    'patient_id' => $patientId,
                    'patient_owner' => $patient->doctor_id,
                    'user_roles' => Auth::user()->getRoleNames(),
                ]);

                return [
                    'success' => false,
                    'data' => [
                        'value' => false,
                        'message' => __('api.unauthorized_action'),
                    ],
                    'status_code' => 403,
                ];
            }

            // Get patient data for AI consultation
            $patientData = $this->preparePatientData($patient);

            // Generate the prompt using the patient's data
            $prompt = $this->chatGPTService->generatePrompt($patientData);

            // Retrieve the doctor ID
            $doctorId = Auth::id();

            // Check doctor trials
            $trialCheck = $this->checkDoctorTrials($doctorId, $patientId);
            if (! $trialCheck['success']) {
                return $trialCheck;
            }

            $trial = $trialCheck['trial'];

            // Send the question to ChatGPT
            $response = $this->chatGPTService->sendMessage($prompt);

            // Save the consultation in the database
            $consultation = $this->saveConsultation($doctorId, $patientId, $prompt, $response);

            // Decrement trial count
            $trial->decrement('trial_count');

            // Log the consultation request
            Log::info('Consultation request sent', [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'trial_count' => $trial->trial_count,
            ]);

            // Return the AI response
            return [
                'success' => true,
                'data' => [
                    'value' => true,
                    'message' => 'Consultation request sent successfully',
                    'response' => $response,
                    'trial_count' => $trial->trial_count,
                ],
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error sending consultation: '.$e->getMessage(), [
                'patient_id' => $patientId,
                'exception' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'An error occurred while sending consultation.',
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Get consultation history for a patient.
     * All users can view consultation history.
     */
    public function getConsultationHistory(int $patientId): array
    {
        try {
            $doctorId = Auth::id();

            // Check if patient exists
            $patient = Patients::find($patientId);
            if (! $patient) {
                return [
                    'success' => false,
                    'data' => [
                        'value' => false,
                        'message' => 'Patient not found',
                    ],
                    'status_code' => 404,
                ];
            }

            // Allow all authenticated users to view consultation history
            Log::info('AI consultation history accessed', [
                'viewer_id' => $doctorId,
                'patient_id' => $patientId,
                'patient_owner' => $patient->doctor_id,
            ]);

            // Check and reset the trial count if the reset date has passed
            $trial = $this->getOrCreateDoctorTrial($doctorId);
            $this->resetTrialIfNeeded($trial);

            // Retrieve consultation history for the patient, sorted by newest
            $history = AIConsultation::where('patient_id', $patientId)
                ->where('doctor_id', $doctorId) // Additional security check
                ->orderBy('created_at', 'desc')
                ->select('id', 'doctor_id', 'patient_id', 'response', 'created_at')
                ->paginate(5);

            // Log the retrieval of consultation history
            Log::info('AI Consultation history retrieved', [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'trial_count' => $trial->trial_count,
                'history_count' => $history->count(),
            ]);

            return [
                'success' => true,
                'data' => [
                    'value' => true,
                    'message' => 'Consultation history retrieved successfully',
                    'trial_count' => $trial->trial_count,
                    'reset_date' => $trial->reset_date,
                    'history' => $history,
                ],
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error retrieving consultation history: '.$e->getMessage(), [
                'patient_id' => $patientId,
                'exception' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'An error occurred while retrieving consultation history.',
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Prepare patient data for AI consultation
     */
    private function preparePatientData(Patients $patient): array
    {
        $sectionsInfos = SectionsInfo::all();
        $data = [];

        // Ensure answers are loaded
        if (!$patient->relationLoaded('answers')) {
            $patient->load('answers');
        }

        // Index answers by question_id for O(1) lookups
        $answersIndexed = $patient->answers->keyBy('question_id');

        // Fetch all questions
        $questions = Questions::with('section')->get();

        // Iterate over each question
        foreach ($questions as $question) {
            // Skip questions with certain IDs
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");

                continue;
            }

            $questionData = [
                'id' => $question->id,
                'section_id' => $question->section->id ?? 'test',
                'section_name' => $question->section->section_name ?? 'Not found',
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];

            // Find the answer for this question using indexed collection
            $answer = $answersIndexed->get($question->id);

            if ($question->type === 'multiple') {
                // Initialize the answer array
                $questionData['answer'] = [
                    'answers' => [], // Initialize answers as an empty array
                    'other_field' => null, // Set other_field to null by default
                ];

                // Find answers for this question
                $questionAnswers = $patient->answers->where('question_id', $question->id);

                // Populate the answers array
                foreach ($questionAnswers as $answer) {
                    if ($answer->type !== 'other') {
                        $questionData['answer']['answers'][] = $answer->answer;
                    }
                    if ($answer->type === 'other') {
                        $questionData['answer']['other_field'] = $answer->answer;
                    }
                }
            } else {
                // For other types of questions, return the answer directly
                $questionData['answer'] = $answer ? $answer->answer : null;
            }

            $data[] = $questionData;
        }

        // Return patient data structure
        return [
            'patient' => $patient,
            'questionData' => $data,
            'sections_infos' => $sectionsInfos,
        ];
    }

    /**
     * Check doctor trials and validate eligibility
     */
    private function checkDoctorTrials(int $doctorId, int $patientId): array
    {
        // Check if the doctor has trials left
        $trial = $this->getOrCreateDoctorTrial($doctorId);

        // Reset trial count if the reset date has passed
        $this->resetTrialIfNeeded($trial);

        // If no trials left, return an error response
        if ($trial->trial_count <= 0) {
            Log::warning('No trials left for this month', [
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'No trials left for this month',
                ],
                'status_code' => 403,
            ];
        }

        return [
            'success' => true,
            'trial' => $trial,
        ];
    }

    /**
     * Get or create doctor trial record
     */
    private function getOrCreateDoctorTrial(int $doctorId): DoctorMonthlyTrial
    {
        return DoctorMonthlyTrial::firstOrCreate(
            ['doctor_id' => $doctorId],
            ['trial_count' => 3, 'reset_date' => now()->addMonth()]
        );
    }

    /**
     * Reset trial count if needed
     */
    private function resetTrialIfNeeded(DoctorMonthlyTrial $trial): void
    {
        if (Carbon::now()->greaterThanOrEqualTo($trial->reset_date)) {
            $trial->update(['trial_count' => 3, 'reset_date' => now()->addMonth()]);
        }
    }

    /**
     * Save consultation to database
     */
    private function saveConsultation(int $doctorId, int $patientId, string $prompt, string $response): AIConsultation
    {
        return AIConsultation::create([
            'doctor_id' => $doctorId,
            'patient_id' => $patientId,
            'question' => $prompt,
            'response' => $response,
        ]);
    }
}
