<?php

namespace App\Modules\Consultations\Services;

use App\Models\Answers;
use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationDoctor;
use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultationService
{
    protected $notificationService;

    public function __construct(ConsultationNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new consultation with associated doctors
     */
    public function createConsultation(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $consultation = Consultation::create([
                'doctor_id' => Auth::id(),
                'patient_id' => $data['patient_id'],
                'consult_message' => $data['consult_message'],
                'status' => 'pending',
            ]);

            $doctors = $data['consult_doctor_ids'];

            foreach ($doctors as $consultDoctorId) {
                ConsultationDoctor::create([
                    'consultation_id' => $consultation->id,
                    'consult_doctor_id' => $consultDoctorId,
                    'status' => 'not replied',
                ]);
            }

            // Send notifications
            $this->notificationService->sendConsultationCreatedNotifications(
                $consultation,
                $doctors,
                $data['patient_id']
            );

            return [
                'value' => true,
                'data' => $consultation,
                'message' => 'Consultation Created Successfully',
            ];
        });
    }

    /**
     * Get consultations sent by the authenticated doctor
     */
    public function getSentRequests(): array
    {
        $consultations = Consultation::where('doctor_id', Auth::id())
            ->with('doctor')
            ->with('patient')
            ->orderBy('updated_at', 'desc')
            ->get();

        $response = [];

        foreach ($consultations as $consultation) {
            $patientId = $consultation->patient_id;
            $patientName = $this->getPatientName($patientId);

            $consultationData = [
                'id' => strval($consultation->id),
                'consult_message' => $consultation->consult_message,
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isSyndicateCard' => $consultation->doctor->isSyndicateCardRequired,
                'patient_id' => strval($consultation->patient_id),
                'patient_name' => $patientName,
                'status' => $consultation->status,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
            ];

            $response[] = $consultationData;
        }

        return $response;
    }

    /**
     * Get consultations received by the authenticated doctor
     */
    public function getReceivedRequests(): array
    {
        $consultationDoctors = ConsultationDoctor::where('consult_doctor_id', Auth::id())
            ->with('consultation')
            ->with('consultDoctor')
            ->with('consultation.doctor')
            ->orderBy('updated_at', 'desc')
            ->get();

        $response = [];

        foreach ($consultationDoctors as $consultationDoctor) {
            $patientId = $consultationDoctor->consultation->patient_id;
            $patientName = $this->getPatientName($patientId);

            $consultationData = [
                'id' => strval($consultationDoctor->consultation->id),
                'consult_message' => $consultationDoctor->consultation->consult_message,
                'doctor_id' => strval($consultationDoctor->consultation->doctor->id),
                'doctor_fname' => $consultationDoctor->consultation->doctor->name,
                'doctor_lname' => $consultationDoctor->consultation->doctor->lname,
                'workingplace' => $consultationDoctor->consultation->doctor->workingplace,
                'image' => $consultationDoctor->consultation->doctor->image,
                'isSyndicateCard' => $consultationDoctor->consultation->doctor->isSyndicateCardRequired,
                'patient_id' => strval($consultationDoctor->consultation->patient_id),
                'patient_name' => $patientName,
                'status' => $consultationDoctor->consultation->status,
                'created_at' => $consultationDoctor->consultation->created_at,
                'updated_at' => $consultationDoctor->consultation->updated_at,
            ];

            $response[] = $consultationData;
        }

        return $response;
    }

    /**
     * Get detailed consultation information
     */
    public function getConsultationDetails(int $id): array
    {
        $consultations = Consultation::where('id', $id)
            ->with([
                'consultationDoctors' => function ($query) {
                    $query->with('consultDoctor:id,name,lname,image,workingplace,isSyndicateCardRequired');
                },
                'doctor:id,name,lname,workingplace,image,isSyndicateCardRequired',
                'patient' => function ($query) {
                    $query->select('id', 'doctor_id', 'updated_at')
                        ->with([
                            'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                            'status:id,patient_id,key,status',
                            'answers:id,patient_id,answer,question_id',
                        ]);
                },
            ])
            ->whereHas('consultationDoctors', function ($query) {
                // Only include Consultations where the authenticated user has a record
            })
            ->get();

        // Pre-fetch patient names to avoid N+1 queries
        $patientIds = $consultations->pluck('patient_id')->unique()->toArray();
        $patientNames = Answers::whereIn('patient_id', $patientIds)
            ->where('question_id', '1')
            ->pluck('answer', 'patient_id');

        $response = [];

        foreach ($consultations as $consultation) {
            // Get patient name from pre-fetched data
            $patientName = $patientNames->get($consultation->patient_id);

            // Use already loaded patient relationship
            $transformedPatient = $this->transformPatientData($consultation->patient);

            $consultationData = [
                'id' => strval($consultation->id),
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isVerified' => $consultation->doctor->isSyndicateCardRequired === 'Verified',
                'status' => $consultation->status,
                'consult_message' => $consultation->consult_message,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
                'patient_info' => $transformedPatient,
                'consultationDoctors' => $consultation->consultationDoctors->map(function ($consultationDoctor) {
                    return [
                        'id' => strval($consultationDoctor->id),
                        'consultation_id' => strval($consultationDoctor->consultation_id),
                        'consult_doctor_id' => strval($consultationDoctor->consult_doctor_id),
                        'consult_doctor_fname' => $consultationDoctor->consultDoctor->name,
                        'consult_doctor_lname' => $consultationDoctor->consultDoctor->lname,
                        'consult_doctor_image' => $consultationDoctor->consultDoctor->image,
                        'workingplace' => $consultationDoctor->consultDoctor->workingplace,
                        'isVerified' => $consultationDoctor->consultDoctor->isSyndicateCardRequired === 'Verified',
                        'reply' => $consultationDoctor->reply ?? 'No reply available',
                        'status' => $consultationDoctor->status,
                        'created_at' => $consultationDoctor->created_at,
                        'updated_at' => $consultationDoctor->updated_at,
                    ];
                }),
            ];

            $response = $consultationData;
        }

        return $response;
    }

    /**
     * Update consultation reply
     */
    public function updateConsultationReply(int $id, array $data): array
    {
        try {
            $user = Auth::user();

            $consultationDoctor = ConsultationDoctor::where('consultation_id', $id)
                ->where('consult_doctor_id', $user->id)
                ->firstOrFail();

            $consultationDoctor->reply = $data['reply'];
            $consultationDoctor->status = 'replied';
            $consultationDoctor->save();

            // Check if all doctors involved in the consultation have replied
            $allReplied = ConsultationDoctor::where('consultation_id', $id)
                ->where('status', '!=', 'replied')
                ->count() === 0;

            // If all doctors have replied, mark the consultation as complete
            if ($allReplied) {
                $consultation = $consultationDoctor->consultation;
                $consultation->status = 'complete';
                $consultation->save();
            }

            // Send notification to the consultation creator
            $doctorId = Consultation::where('id', $id)->value('doctor_id');

            $this->notificationService->sendConsultationReplyNotification(
                $user,
                $doctorId,
                $id,
                $data['patient_id'] ?? null
            );

            Log::info('Consultation request updated successfully.', [
                'consultation_id' => $id,
                'doctor_id' => $user->id,
                'reply' => $data['reply'],
                'all_replied' => $allReplied,
            ]);

            return [
                'message' => 'Consultation request updated successfully',
                'data' => [
                    'consultation_id' => $id,
                    'doctor_id' => $user->id,
                    'reply' => $data['reply'],
                    'all_replied' => $allReplied,
                ],
            ];
        } catch (ModelNotFoundException $e) {
            Log::warning('Consultation doctor not found.', [
                'consultation_id' => $id,
                'doctor_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Search for doctors for consultation
     */
    public function searchDoctors(string $data): array
    {
        try {
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Explode the input string into words
            $keywords = explode(' ', $data);

            $users = User::select('id', 'name', 'lname', 'email', 'phone', 'specialty', 'workingplace', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->when(! $isAdminOrTester, function ($query) {
                    return $query->where('id', '!=', Auth::id());
                })
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $word) {
                        $query->where(function ($subQuery) use ($word) {
                            $subQuery->where('name', 'like', '%'.$word.'%')
                                ->orWhere('lname', 'like', '%'.$word.'%')
                                ->orWhere('email', 'like', '%'.$word.'%')
                                ->orWhere('phone', 'like', '%'.$word.'%');
                        });
                    }
                })
                ->withCount('patients')
                ->selectSub(function ($query) {
                    $query->selectRaw('COALESCE(score, 0)')
                        ->from('scores')
                        ->whereColumn('users.id', 'scores.doctor_id')
                        ->limit(1);
                }, 'score')
                ->orderByRaw('COALESCE(score, 0) DESC, patients_count DESC')
                ->get()
                ->map(function ($user) {
                    $user->patients_count = strval($user->patients_count);

                    return $user;
                });

            return [
                'value' => true,
                'data' => $users,
            ];
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            throw $e;
        }
    }

    /**
     * Get patient name by ID
     */
    private function getPatientName(int $patientId): ?string
    {
        return Answers::where('patient_id', $patientId)
            ->where('question_id', '1')
            ->pluck('answer')
            ->first();
    }

    /**
     * Get patient details with related data
     */
    private function getPatientDetails(int $patientId): ?Patients
    {
        return Patients::select('id', 'doctor_id', 'updated_at')
            ->where('id', $patientId)
            ->with(['doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
            }])
            ->with(['status' => function ($query) {
                $query->select('id', 'patient_id', 'key', 'status');
            }])
            ->with(['answers' => function ($query) {
                $query->select('id', 'patient_id', 'answer', 'question_id');
            }])
            ->latest('updated_at')
            ->first();
    }

    /**
     * Transform patient data for response
     */
    private function transformPatientData(?Patients $patient): ?array
    {
        if (! $patient) {
            return null;
        }

        // Create indexed collections for O(1) lookups instead of O(n) where() calls
        $statusByKey = $patient->status->keyBy('key');
        $answersByQuestionId = $patient->answers->keyBy('question_id');

        // Use indexed collections for efficient lookups
        $submitStatus = optional($statusByKey->get('submit_status'))->status;
        $outcomeStatus = optional($statusByKey->get('outcome_status'))->status;

        $nameAnswer = optional($answersByQuestionId->get(1))->answer;
        $hospitalAnswer = optional($answersByQuestionId->get(2))->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => $patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
            ],
        ];
    }
}
