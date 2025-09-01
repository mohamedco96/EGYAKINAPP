<?php

namespace App\Modules\Consultations\Services;

use App\Models\Answers;
use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\ConsultationDoctor;
use App\Modules\Consultations\Models\ConsultationReply;
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
                'is_open' => true,
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
                'is_open' => $consultation->is_open,
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
                'is_open' => $consultationDoctor->consultation->is_open,
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
                    $query->with([
                        'consultDoctor:id,name,lname,image,workingplace,isSyndicateCardRequired',
                        'replies' => function ($repliesQuery) {
                            $repliesQuery->select('id', 'consultation_doctor_id', 'reply', 'created_at')
                                ->orderBy('created_at', 'asc');
                        },
                    ]);
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

            // Collect all replies as separate doctor entries in chronological order
            $consultationDoctorsWithReplies = collect();

            foreach ($consultation->consultationDoctors as $consultationDoctor) {
                foreach ($consultationDoctor->replies as $reply) {
                    $consultationDoctorsWithReplies->push([
                        'id' => strval($consultationDoctor->id),
                        'consultation_id' => strval($consultationDoctor->consultation_id),
                        'consult_doctor_id' => strval($consultationDoctor->consult_doctor_id),
                        'consult_doctor_fname' => $consultationDoctor->consultDoctor->name,
                        'consult_doctor_lname' => $consultationDoctor->consultDoctor->lname,
                        'consult_doctor_image' => $consultationDoctor->consultDoctor->image,
                        'workingplace' => $consultationDoctor->consultDoctor->workingplace,
                        'isVerified' => $consultationDoctor->consultDoctor->isSyndicateCardRequired === 'Verified',
                        'reply' => $reply->reply,
                        'status' => $consultationDoctor->status,
                        'created_at' => $reply->created_at,
                        'updated_at' => $reply->created_at,
                    ]);
                }
            }

            // Sort all entries by reply created_at in ascending order
            $sortedConsultationDoctors = $consultationDoctorsWithReplies->sortBy('created_at')->values();

            $consultationData = [
                'id' => strval($consultation->id),
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isVerified' => $consultation->doctor->isSyndicateCardRequired === 'Verified',
                'status' => $consultation->status,
                'is_open' => $consultation->is_open,
                'consult_message' => $consultation->consult_message,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
                'patient_info' => $transformedPatient,
                'consultationDoctors' => $sortedConsultationDoctors,
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

            $consultation = Consultation::where('id', $id)->first();

            if (! $consultation) {
                return [
                    'message' => 'Consultation not found.',
                ];
            }

            if (! $consultation->is_open) {
                return [
                    'message' => 'Cannot reply to a closed consultation.',
                ];
            }

            $consultationDoctor = ConsultationDoctor::where('consultation_id', $id)
                ->where('consult_doctor_id', $user->id)
                ->firstOrFail();

            // For backward compatibility, keep the first reply in the main field
            if (empty($consultationDoctor->reply)) {
                $consultationDoctor->reply = $data['reply'];
            }
            $consultationDoctor->status = 'replied';
            $consultationDoctor->save();

            // Also store in consultation_replies table for consistency
            ConsultationReply::create([
                'consultation_doctor_id' => $consultationDoctor->id,
                'reply' => $data['reply'],
            ]);

            // Note: We no longer automatically mark consultation as complete when all doctors reply
            // The consultation owner controls when to close the discussion

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
            ]);

            return [
                'message' => 'Consultation request updated successfully',
                'data' => [
                    'consultation_id' => $id,
                    'doctor_id' => $user->id,
                    'reply' => $data['reply'],
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

    /**
     * Add new doctors to an existing consultation
     */
    public function addDoctorsToConsultation(int $consultationId, array $data): array
    {
        try {
            $consultation = Consultation::where('id', $consultationId)
                ->where('doctor_id', Auth::id())
                ->firstOrFail();

            if (! $consultation->is_open) {
                return [
                    'value' => false,
                    'message' => 'Cannot add doctors to a closed consultation.',
                ];
            }

            $newDoctorIds = $data['consult_doctor_ids'];

            // Get existing doctor IDs to avoid duplicates
            $existingDoctorIds = ConsultationDoctor::where('consultation_id', $consultationId)
                ->pluck('consult_doctor_id')
                ->toArray();

            $doctorsToAdd = array_diff($newDoctorIds, $existingDoctorIds);

            if (empty($doctorsToAdd)) {
                return [
                    'value' => false,
                    'message' => 'All selected doctors are already part of this consultation.',
                ];
            }

            return DB::transaction(function () use ($consultation, $doctorsToAdd) {
                foreach ($doctorsToAdd as $doctorId) {
                    ConsultationDoctor::create([
                        'consultation_id' => $consultation->id,
                        'consult_doctor_id' => $doctorId,
                        'status' => 'not replied',
                    ]);
                }

                // Send notifications to new doctors
                $this->notificationService->sendConsultationCreatedNotifications(
                    $consultation,
                    $doctorsToAdd,
                    $consultation->patient_id
                );

                return [
                    'value' => true,
                    'message' => 'Doctors added to consultation successfully.',
                    'added_doctors_count' => count($doctorsToAdd),
                ];
            });
        } catch (ModelNotFoundException $e) {
            return [
                'value' => false,
                'message' => 'Consultation not found or you are not authorized to modify it.',
            ];
        } catch (\Exception $e) {
            Log::error('Error adding doctors to consultation.', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to add doctors to consultation.',
            ];
        }
    }

    /**
     * Toggle consultation open/close status
     */
    public function toggleConsultationStatus(int $consultationId, array $data): array
    {
        try {
            $consultation = Consultation::where('id', $consultationId)
                ->where('doctor_id', Auth::id())
                ->firstOrFail();

            $consultation->is_open = $data['is_open'];
            $consultation->save();

            $status = $data['is_open'] ? 'opened' : 'closed';

            return [
                'value' => true,
                'message' => "Consultation {$status} successfully.",
                'data' => [
                    'consultation_id' => $consultationId,
                    'is_open' => $consultation->is_open,
                ],
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'value' => false,
                'message' => 'Consultation not found or you are not authorized to modify it.',
            ];
        } catch (\Exception $e) {
            Log::error('Error toggling consultation status.', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to update consultation status.',
            ];
        }
    }

    /**
     * Get consultation members (doctors involved)
     */
    public function getConsultationMembers(int $consultationId): array
    {
        try {
            $consultation = Consultation::where('id', $consultationId)
                ->with('doctor:id,name,lname,email,phone,specialty,workingplace,image,syndicate_card,isSyndicateCardRequired')
                ->first();

            if (! $consultation) {
                return [
                    'value' => false,
                    'message' => 'Consultation not found.',
                ];
            }

            // Check if user has access to this consultation
            $hasAccess = $consultation->doctor_id === Auth::id() ||
                ConsultationDoctor::where('consultation_id', $consultationId)
                    ->where('consult_doctor_id', Auth::id())
                    ->exists();

            if (! $hasAccess) {
                return [
                    'value' => false,
                    'message' => 'You are not authorized to view this consultation.',
                ];
            }

            $consultationDoctors = ConsultationDoctor::where('consultation_id', $consultationId)
                ->with('consultDoctor:id,name,lname,email,phone,specialty,workingplace,image,syndicate_card,isSyndicateCardRequired')
                ->get();

            // Get all doctor IDs to fetch additional data
            $doctorIds = collect([$consultation->doctor_id])
                ->merge($consultationDoctors->pluck('consult_doctor_id'))
                ->unique()
                ->values();

            // Fetch all doctors with additional data (patients count and score)
            $doctors = User::whereIn('id', $doctorIds)
                ->select('id', 'name', 'lname', 'email', 'phone', 'specialty', 'workingplace', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->withCount('patients')
                ->selectSub(function ($query) {
                    $query->selectRaw('COALESCE(score, 0)')
                        ->from('scores')
                        ->whereColumn('users.id', 'scores.doctor_id')
                        ->limit(1);
                }, 'score')
                ->get()
                ->keyBy('id')
                ->map(function ($user) {
                    $user->patients_count = strval($user->patients_count);

                    return $user;
                });

            $members = [];

            // Add consultation creator
            $creator = $doctors->get($consultation->doctor_id);
            if ($creator) {
                $members[] = [
                    'id' => $creator->id,
                    'name' => $creator->name,
                    'lname' => $creator->lname,
                    'email' => $creator->email,
                    'phone' => $creator->phone,
                    'specialty' => $creator->specialty,
                    'workingplace' => $creator->workingplace,
                    'image' => $creator->image,
                    'syndicate_card' => $creator->syndicate_card,
                    'isSyndicateCardRequired' => $creator->isSyndicateCardRequired,
                    'patients_count' => $creator->patients_count,
                    'score' => $creator->score,
                    'role' => 'creator',
                    'status' => 'creator',
                ];
            }

            // Add consulted doctors
            foreach ($consultationDoctors as $consultationDoctor) {
                $consultedDoctor = $doctors->get($consultationDoctor->consult_doctor_id);
                if ($consultedDoctor) {
                    $members[] = [
                        'id' => strval($consultedDoctor->id),
                        'name' => $consultedDoctor->name,
                        'lname' => $consultedDoctor->lname,
                        'email' => $consultedDoctor->email,
                        'phone' => $consultedDoctor->phone,
                        'specialty' => $consultedDoctor->specialty,
                        'workingplace' => $consultedDoctor->workingplace,
                        'image' => $consultedDoctor->image,
                        'syndicate_card' => $consultedDoctor->syndicate_card,
                        'isSyndicateCardRequired' => $consultedDoctor->isSyndicateCardRequired,
                        'patients_count' => $consultedDoctor->patients_count,
                        'score' => $consultedDoctor->score,
                        'role' => 'consulted',
                        'status' => $consultationDoctor->status,
                    ];
                }
            }

            return [
                'value' => true,
                'data' => $members,
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving consultation members.', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to retrieve consultation members.',
            ];
        }
    }

    /**
     * Add a new reply to consultation (allows multiple replies)
     */
    public function addConsultationReply(int $consultationId, array $data): array
    {
        try {
            $consultation = Consultation::where('id', $consultationId)->first();

            if (! $consultation) {
                return [
                    'value' => false,
                    'message' => 'Consultation not found.',
                ];
            }

            if (! $consultation->is_open) {
                return [
                    'value' => false,
                    'message' => 'Cannot reply to a closed consultation.',
                ];
            }

            $user = Auth::user();
            $consultationDoctor = ConsultationDoctor::where('consultation_id', $consultationId)
                ->where('consult_doctor_id', $user->id)
                ->first();

            if (! $consultationDoctor) {
                return [
                    'value' => false,
                    'message' => 'You are not authorized to reply to this consultation.',
                ];
            }

            return DB::transaction(function () use ($consultationDoctor, $data, $consultation, $user) {
                // Update status to 'replied' if this is the first reply
                if ($consultationDoctor->status === 'not replied') {
                    $consultationDoctor->status = 'replied';
                    $consultationDoctor->save();
                }

                // Always create new reply record in consultation_replies table
                $reply = ConsultationReply::create([
                    'consultation_doctor_id' => $consultationDoctor->id,
                    'reply' => $data['reply'],
                ]);

                // Send notification
                $this->notificationService->sendConsultationReplyNotification(
                    $user,
                    $consultation->doctor_id,
                    $consultation->id,
                    $data['patient_id'] ?? null
                );

                Log::info('New consultation reply added.', [
                    'consultation_id' => $consultation->id,
                    'doctor_id' => $user->id,
                    'reply_id' => $reply->id,
                ]);

                return [
                    'value' => true,
                    'message' => 'Reply added successfully.',
                    'data' => [
                        'reply_id' => $reply->id,
                        'consultation_id' => $consultation->id,
                        'doctor_id' => $user->id,
                        'created_at' => $reply->created_at,
                    ],
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error adding consultation reply.', [
                'consultation_id' => $consultationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to add reply.',
            ];
        }
    }
}
