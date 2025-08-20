<?php

namespace App\Modules\Recommendations\Services;

use App\Models\SectionsInfo;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use App\Modules\Recommendations\Models\Recommendation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecommendationService
{
    /**
     * Get all recommendations for a patient.
     */
    public function getPatientRecommendations(int $patientId): array
    {
        try {
            Log::info('Fetching recommendations for patient', ['patient_id' => $patientId]);

            $patient = Patients::findOrFail($patientId);
            $recommendations = $patient->recommendations()->get();

            Log::info('Successfully fetched recommendations', ['patient_id' => $patientId, 'count' => $recommendations->count()]);

            return [
                'value' => true,
                'data' => $recommendations,
                'message' => 'Recommendations fetched successfully.',
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID '.$patientId.' not found.',
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Error fetching recommendations.',
            ];
        }
    }

    /**
     * Create new recommendations for a patient.
     */
    public function createRecommendations(int $patientId, array $recommendations): array
    {
        try {
            Log::info('Creating recommendations for patient', ['patient_id' => $patientId]);

            $patient = Patients::findOrFail($patientId);

            $result = DB::transaction(function () use ($patient, $patientId, $recommendations) {
                $recommendationModels = collect($recommendations)->map(function ($item) use ($patientId) {
                    $data = [
                        'patient_id' => $patientId,
                        'type' => $item['type'] ?? 'rec', // Default to 'rec' for backward compatibility
                        'content' => $item['content'] ?? null,
                        'dose_name' => $item['dose_name'] ?? null,
                        'dose' => $item['dose'] ?? null,
                        'route' => $item['route'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                    ];

                    return new Recommendation($data);
                });

                $savedRecommendations = $patient->recommendations()->saveMany($recommendationModels);

                // Get the section ID for "Discharge Recommendations"
                $dischargeSection = SectionsInfo::where('section_name', 'Discharge Recommendations')->first();

                if ($dischargeSection) {
                    $doctorId = Auth::id();

                    // Check if section status already exists
                    $patientSectionStatus = PatientStatus::where('patient_id', $patientId)
                        ->where('key', 'section_'.$dischargeSection->id)
                        ->first();

                    if ($patientSectionStatus) {
                        // Update existing section status
                        $patientSectionStatus->touch();
                    } else {
                        // Create new section status
                        PatientStatus::create([
                            'doctor_id' => $doctorId,
                            'patient_id' => $patientId,
                            'key' => 'section_'.$dischargeSection->id,
                            'status' => true,
                        ]);
                    }
                }

                return $savedRecommendations;
            });

            Log::info('Successfully created recommendations', ['patient_id' => $patientId, 'count' => count($recommendations)]);

            return [
                'value' => true,
                'data' => $result,
                'message' => 'Recommendations created successfully.',
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID '.$patientId.' not found.',
            ];
        } catch (\Exception $e) {
            Log::error('Error creating recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Error creating recommendations.',
            ];
        }
    }

    /**
     * Update recommendations for a patient.
     */
    public function updateRecommendations(int $patientId, array $recommendations): array
    {
        try {
            Log::info('Updating recommendations for patient', ['patient_id' => $patientId]);

            $patient = Patients::findOrFail($patientId);

            $result = DB::transaction(function () use ($patient, $patientId, $recommendations) {
                // Delete existing recommendations
                $patient->recommendations()->delete();

                // Create new recommendations
                $recommendationModels = collect($recommendations)->map(function ($item) use ($patientId) {
                    $data = [
                        'patient_id' => $patientId,
                        'type' => $item['type'] ?? 'rec', // Default to 'rec' for backward compatibility
                        'content' => $item['content'] ?? null,
                        'dose_name' => $item['dose_name'] ?? null,
                        'dose' => $item['dose'] ?? null,
                        'route' => $item['route'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                    ];

                    return new Recommendation($data);
                });

                return $patient->recommendations()->saveMany($recommendationModels);
            });

            Log::info('Successfully updated recommendations', ['patient_id' => $patientId, 'count' => count($recommendations)]);

            return [
                'value' => true,
                'data' => $result,
                'message' => 'Recommendations updated successfully.',
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID '.$patientId.' not found.',
            ];
        } catch (\Exception $e) {
            Log::error('Error updating recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Error updating recommendations.',
            ];
        }
    }

    /**
     * Delete recommendations for a patient.
     */
    public function deleteRecommendations(int $patientId, array $ids): array
    {
        try {
            Log::info('Deleting recommendations for patient', ['patient_id' => $patientId, 'ids' => $ids]);

            $patient = Patients::findOrFail($patientId);

            $result = DB::transaction(function () use ($patient, $ids) {
                if ($ids[0] === 0) {
                    return $patient->recommendations()->delete();
                }

                return $patient->recommendations()->whereIn('id', $ids)->delete();
            });

            Log::info('Successfully deleted recommendations', ['patient_id' => $patientId, 'deleted_count' => $result]);

            return [
                'value' => true,
                'message' => 'Recommendations deleted successfully.',
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID '.$patientId.' not found.',
            ];
        } catch (\Exception $e) {
            Log::error('Error deleting recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);

            return [
                'value' => false,
                'data' => null,
                'message' => 'Error deleting recommendations.',
            ];
        }
    }
}
