<?php

namespace App\Modules\Sections\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Patients\Models\Patients;
use App\Modules\Patients\Models\PatientStatus;
use App\Modules\Sections\Requests\UpdateFinalSubmitRequest;
use App\Modules\Sections\Services\GfrCalculationService;
use App\Modules\Sections\Services\ScoringService;
use App\Modules\Sections\Services\SectionManagementService;
use Illuminate\Support\Facades\Log;

class SectionsController extends Controller
{
    protected $gfrCalculationService;

    protected $scoringService;

    protected $sectionManagementService;

    public function __construct(
        GfrCalculationService $gfrCalculationService,
        ScoringService $scoringService,
        SectionManagementService $sectionManagementService
    ) {
        $this->gfrCalculationService = $gfrCalculationService;
        $this->scoringService = $scoringService;
        $this->sectionManagementService = $sectionManagementService;
    }

    /**
     * Update the final submit status for a patient.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFinalSubmit(UpdateFinalSubmitRequest $request, int $patientId)
    {
        try {
            // Fetch patient submit status
            $patientSubmitStatus = PatientStatus::where('patient_id', $patientId)
                ->where('key', 'submit_status')
                ->first();

            // Handle case where patient submit status is not found
            if (! $patientSubmitStatus) {
                Log::error("Patient submit status not found for patient ID: $patientId");

                return response()->json([
                    'value' => false,
                    'message' => 'Patient not found',
                ], 404);
            }

            // Update submit status to true
            $patientSubmitStatus->update(['status' => true]);

            // Process scoring system
            $this->scoringService->processFinalSubmitScoring($patientId);

            // Update patient timestamp
            Patients::where('id', $patientId)->update(['updated_at' => now()]);

            Log::info("Final submit updated successfully for patient ID: $patientId");

            return response()->json([
                'value' => true,
                'message' => 'Final Submit Updated Successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error updating final submit for patient ID: $patientId. Error: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'Error updating final submit.',
            ], 500);
        }
    }

    /**
     * Show questions and answers for a specific section and patient.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showQuestionsAnswers(int $sectionId, int $patientId)
    {
        try {
            // Get questions and answers data
            $data = $this->sectionManagementService->getQuestionsAndAnswers($sectionId, $patientId);

            // Prepare response based on section type
            if ($sectionId == 8) {
                $submitter = $this->sectionManagementService->getSubmitterInfo($patientId);

                $response = [
                    'value' => true,
                    'Submitter' => $submitter,
                    'data' => $data,
                ];
            } else {
                $response = [
                    'value' => true,
                    'data' => $data,
                ];
            }

            Log::info("Questions and answers retrieved successfully for section ID {$sectionId} and patient ID {$patientId}.");

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error while fetching questions and answers: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show sections and their statuses for a patient (Legacy format for backward compatibility).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSections(int $patientId)
    {
        try {
            // Get patient basic data
            $patientData = $this->sectionManagementService->getPatientBasicData($patientId);

            // Validate patient exists
            if (! $patientData['patient_name']) {
                Log::error("Patient name not found for patient ID: $patientId");

                return response()->json([
                    'value' => false,
                    'message' => 'Patient not found for the given patient ID.',
                ], 404);
            }

            // Get sections data
            $sectionsData = $this->sectionManagementService->getSectionsData($patientId);

            if (empty($sectionsData)) {
                Log::warning("Sections not found for patient ID: $patientId");

                return response()->json([
                    'value' => false,
                    'message' => 'Sections not found for the given patient ID.',
                ], 404);
            }

            // Get patient GFR data and calculate GFR values (OLD FORMAT - no localization)
            $gfrData = $this->sectionManagementService->getPatientGfrData($patientId);
            $gfrValues = $this->gfrCalculationService->calculateAllGfrValuesLegacy($gfrData);

            Log::info("Showing sections for patient ID: $patientId (LEGACY FORMAT)", [
                'submit_status' => $patientData['submit_status'],
                'patient_name' => $patientData['patient_name'],
                'doctor_id' => $patientData['doctor_id'],
                'sections_count' => count($sectionsData),
                'gfr' => $gfrValues,
            ]);

            return response()->json([
                'value' => true,
                'submit_status' => $patientData['submit_status'],
                'patient_name' => $patientData['patient_name'],
                'doctor_Id' => $patientData['doctor_id'],
                'gfr' => $gfrValues,
                'data' => $sectionsData,
            ]);

        } catch (\Exception $e) {
            Log::error("Error while showing sections for patient ID: $patientId. Error: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show sections and their statuses for a patient (V1 format with localization).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSectionsV1(int $patientId)
    {
        try {
            // Get patient basic data
            $patientData = $this->sectionManagementService->getPatientBasicData($patientId);

            // Validate patient exists
            if (! $patientData['patient_name']) {
                Log::error("Patient name not found for patient ID: $patientId");

                return response()->json([
                    'value' => false,
                    'message' => 'Patient not found for the given patient ID.',
                ], 404);
            }

            // Get sections data
            $sectionsData = $this->sectionManagementService->getSectionsData($patientId);

            if (empty($sectionsData)) {
                Log::warning("Sections not found for patient ID: $patientId");

                return response()->json([
                    'value' => false,
                    'message' => 'Sections not found for the given patient ID.',
                ], 404);
            }

            // Get patient GFR data and calculate GFR values (NEW FORMAT - with localization)
            $gfrData = $this->sectionManagementService->getPatientGfrData($patientId);
            $gfrValues = $this->gfrCalculationService->calculateAllGfrValues($gfrData);

            Log::info("Showing sections for patient ID: $patientId (V1 FORMAT)", [
                'submit_status' => $patientData['submit_status'],
                'patient_name' => $patientData['patient_name'],
                'doctor_id' => $patientData['doctor_id'],
                'sections_count' => count($sectionsData),
                'gfr' => $gfrValues,
            ]);

            return response()->json([
                'value' => true,
                'submit_status' => $patientData['submit_status'],
                'patient_name' => $patientData['patient_name'],
                'doctor_Id' => $patientData['doctor_id'],
                'gfr' => $gfrValues,
                'data' => $sectionsData,
            ]);

        } catch (\Exception $e) {
            Log::error("Error while showing sections for patient ID: $patientId (V1). Error: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
}
