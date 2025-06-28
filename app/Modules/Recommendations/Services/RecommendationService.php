<?php

namespace App\Modules\Recommendations\Services;

use App\Modules\Patients\Models\Patients;
use App\Modules\Recommendations\Models\Recommendation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RecommendationService
{
    /**
     * Get all recommendations for a patient.
     *
     * @param int $patientId
     * @return array
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
                'message' => 'Recommendations fetched successfully.'
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID ' . $patientId . ' not found.'
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Error fetching recommendations.'
            ];
        }
    }

    /**
     * Create new recommendations for a patient.
     *
     * @param int $patientId
     * @param array $recommendations
     * @return array
     */
    public function createRecommendations(int $patientId, array $recommendations): array
    {
        try {
            Log::info('Creating recommendations for patient', ['patient_id' => $patientId]);
            
            $patient = Patients::findOrFail($patientId);

            $result = DB::transaction(function () use ($patient, $patientId, $recommendations) {
                $recommendationModels = collect($recommendations)->map(function ($item) use ($patientId) {
                    return new Recommendation([
                        'patient_id' => $patientId,
                        'dose_name' => $item['dose_name'],
                        'dose' => $item['dose'],
                        'route' => $item['route'],
                        'frequency' => $item['frequency'],
                        'duration' => $item['duration'],
                    ]);
                });

                return $patient->recommendations()->saveMany($recommendationModels);
            });

            Log::info('Successfully created recommendations', ['patient_id' => $patientId, 'count' => count($recommendations)]);
            
            return [
                'value' => true,
                'data' => $result,
                'message' => 'Recommendations created successfully.'
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID ' . $patientId . ' not found.'
            ];
        } catch (\Exception $e) {
            Log::error('Error creating recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Error creating recommendations.'
            ];
        }
    }

    /**
     * Update recommendations for a patient.
     *
     * @param int $patientId
     * @param array $recommendations
     * @return array
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
                    return new Recommendation([
                        'patient_id' => $patientId,
                        'dose_name' => $item['dose_name'],
                        'dose' => $item['dose'],
                        'route' => $item['route'],
                        'frequency' => $item['frequency'],
                        'duration' => $item['duration'],
                    ]);
                });

                return $patient->recommendations()->saveMany($recommendationModels);
            });

            Log::info('Successfully updated recommendations', ['patient_id' => $patientId, 'count' => count($recommendations)]);
            
            return [
                'value' => true,
                'data' => $result,
                'message' => 'Recommendations updated successfully.'
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID ' . $patientId . ' not found.'
            ];
        } catch (\Exception $e) {
            Log::error('Error updating recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Error updating recommendations.'
            ];
        }
    }

    /**
     * Delete recommendations for a patient.
     *
     * @param int $patientId
     * @param array $ids
     * @return array
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
                'message' => 'Recommendations deleted successfully.'
            ];
        } catch (ModelNotFoundException $e) {
            Log::error('Patient not found', ['patient_id' => $patientId]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Patient with ID ' . $patientId . ' not found.'
            ];
        } catch (\Exception $e) {
            Log::error('Error deleting recommendations', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
            return [
                'value' => false,
                'data' => null,
                'message' => 'Error deleting recommendations.'
            ];
        }
    }
}
