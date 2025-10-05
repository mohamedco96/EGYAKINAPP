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
     * Matches format from App\Modules\Auth\Services\AuthService::getDoctorPatients
     */
    public function getMarkedPatients(int $perPage = 10): array
    {
        try {
            $user = Auth::user();

            // Get marked patients with relationships (matching getDoctorPatients structure)
            $markedPatientsQuery = $user->markedPatients()
                ->select('patients.id', 'patients.doctor_id', 'patients.updated_at')
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status' => function ($query) {
                        $query->select('id', 'patient_id', 'key', 'status')
                            ->whereIn('key', ['submit_status', 'outcome_status']);
                    },
                    'answers' => function ($query) {
                        $query->select('id', 'patient_id', 'answer', 'question_id')
                            ->whereIn('question_id', [1, 2]);
                    },
                ])
                ->latest('marked_patients.created_at'); // Order by when they were marked

            // Paginate results
            $paginatedPatients = $markedPatientsQuery->paginate($perPage);

            // Transform the paginated results (matching getDoctorPatients format)
            $transformedData = collect($paginatedPatients->items())->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => optional($patient->answers->where('question_id', 1)->first())->answer,
                    'hospital' => optional($patient->answers->where('question_id', 2)->first())->answer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => optional($patient->status->where('key', 'submit_status')->first())->status ?? false,
                        'outcome_status' => optional($patient->status->where('key', 'outcome_status')->first())->status ?? false,
                    ],
                ];
            })->values()->all();

            // Create a new paginator with the transformed data
            $result = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedData,
                $paginatedPatients->total(),
                $perPage,
                $paginatedPatients->currentPage(),
                [
                    'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );

            Log::info('Retrieved marked patients', [
                'user_id' => $user->id,
                'count' => $paginatedPatients->total(),
            ]);

            // Match the exact format from getDoctorPatients
            return [
                'value' => true,
                'data' => $result,
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error getting marked patients: '.$e->getMessage(), [
                'user_id' => Auth::id(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to retrieve marked patients.',
                'status_code' => 500,
            ];
        }
    }
}
