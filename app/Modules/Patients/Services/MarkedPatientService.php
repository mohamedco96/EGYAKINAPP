<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patients;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MarkedPatientService
{
    /**
     * Add a patient to marked list
     */
    public function addToMarked(int $patientId): array
    {
        try {
            $user = Auth::user();

            // Check if patient exists
            $patient = Patients::find($patientId);
            if (! $patient) {
                return [
                    'success' => false,
                    'message' => 'Patient not found.',
                ];
            }

            // Check if already marked
            if ($user->markedPatients()->where('patient_id', $patientId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'Patient is already marked.',
                ];
            }

            // Add to marked list
            $user->markedPatients()->attach($patientId);

            Log::info('Patient marked', [
                'user_id' => $user->id,
                'patient_id' => $patientId,
            ]);

            return [
                'success' => true,
                'message' => 'Patient marked successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Error marking patient: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'patient_id' => $patientId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to mark patient.',
            ];
        }
    }

    /**
     * Remove a patient from marked list
     */
    public function removeFromMarked(int $patientId): array
    {
        try {
            $user = Auth::user();

            // Check if patient is marked
            if (! $user->markedPatients()->where('patient_id', $patientId)->exists()) {
                return [
                    'success' => false,
                    'message' => 'Patient is not marked.',
                ];
            }

            // Remove from marked list
            $user->markedPatients()->detach($patientId);

            Log::info('Patient unmarked', [
                'user_id' => $user->id,
                'patient_id' => $patientId,
            ]);

            return [
                'success' => true,
                'message' => 'Patient unmarked successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Error unmarking patient: '.$e->getMessage(), [
                'user_id' => Auth::id(),
                'patient_id' => $patientId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to unmark patient.',
            ];
        }
    }

    /**
     * Get marked patients count for the authenticated user
     */
    public function getMarkedCount(): int
    {
        try {
            return Auth::user()->markedPatients()->count();
        } catch (\Exception $e) {
            Log::error('Error getting marked patients count: '.$e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return 0;
        }
    }

    /**
     * Get marked patients with pagination and transformation
     */
    public function getMarkedPatients(int $perPage = 10): array
    {
        try {
            $user = Auth::user();

            // Get marked patients with relationships
            $markedPatientsQuery = $user->markedPatients()
                ->select('patients.id', 'patients.doctor_id', 'patients.updated_at')
                ->with([
                    'doctor:id,name',
                    'answers:id,patient_id,answer,question_id',
                    'status:id,patient_id,key,status',
                ])
                ->latest('marked_patients.created_at'); // Order by when they were marked

            // Paginate results
            $paginatedPatients = $markedPatientsQuery->paginate($perPage);

            // Transform patient data
            $transformedPatients = $paginatedPatients->getCollection()->map(function ($patient) {
                return $this->transformPatientData($patient);
            });

            return [
                'data' => $transformedPatients,
                'pagination' => [
                    'current_page' => $paginatedPatients->currentPage(),
                    'per_page' => $paginatedPatients->perPage(),
                    'total' => $paginatedPatients->total(),
                    'last_page' => $paginatedPatients->lastPage(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error getting marked patients: '.$e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            throw $e;
        }
    }

    /**
     * Transform patient data for response
     * Matches the structure from PatientFilterService
     */
    private function transformPatientData($patient): array
    {
        // Create indexed collections for O(1) lookups
        $statusByKey = $patient->status->keyBy('key');
        $answersByQuestionId = $patient->answers->keyBy('question_id');

        // Use indexed collections for efficient lookups
        $submitStatus = optional($statusByKey->get('submit_status'))->status;
        $outcomeStatus = optional($statusByKey->get('outcome_status'))->status;

        $nameAnswer = optional($answersByQuestionId->get(1))->answer;
        $hospitalAnswer = optional($answersByQuestionId->get(2))->answer;

        return [
            'id' => $patient->id,
            'doctor_id' => (int) $patient->doctor_id,
            'name' => $nameAnswer,
            'hospital' => $hospitalAnswer,
            'updated_at' => $patient->updated_at,
            'doctor' => $patient->doctor,
            'answers' => $patient->answers,
            'sections' => [
                'patient_id' => $patient->id,
                'submit_status' => $submitStatus ?? false,
                'outcome_status' => $outcomeStatus ?? false,
            ],
            'is_marked' => true, // Always true for marked patients list
        ];
    }
}
