<?php

namespace App\Modules\Consultations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consultations\Services\ConsultationService;
use App\Modules\Consultations\Requests\StoreConsultationRequest;
use App\Modules\Consultations\Requests\UpdateConsultationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConsultationController extends Controller
{
    protected $consultationService;

    public function __construct(ConsultationService $consultationService)
    {
        $this->consultationService = $consultationService;
    }

    /**
     * Store a newly created consultation in storage.
     */
    public function store(StoreConsultationRequest $request): JsonResponse
    {
        try {
            $result = $this->consultationService->createConsultation($request->validated());
            return response()->json($result, 201);
        } catch (\Exception $e) {
            Log::error('Error creating consultation.', [
                'request_data' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to create consultation.',
            ], 500);
        }
    }

    /**
     * Get consultations sent by the authenticated doctor.
     */
    public function sentRequests(): JsonResponse
    {
        try {
            $result = $this->consultationService->getSentRequests();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error retrieving sent consultation requests.', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve sent consultation requests.',
            ], 500);
        }
    }

    /**
     * Get consultations received by the authenticated doctor.
     */
    public function receivedRequests(): JsonResponse
    {
        try {
            $result = $this->consultationService->getReceivedRequests();
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error retrieving received consultation requests.', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve received consultation requests.',
            ], 500);
        }
    }

    /**
     * Display the specified consultation details.
     */
    public function consultationDetails(int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->getConsultationDetails($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error retrieving consultation details.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve consultation details.',
            ], 500);
        }
    }

    /**
     * Update the specified consultation reply.
     */
    public function update(UpdateConsultationRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->updateConsultationReply($id, $request->validated());
            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Consultation doctor not found for the provided consultation ID.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('An error occurred while updating the consultation request.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the consultation request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search for doctors for consultation.
     */
    public function consultationSearch(string $data): JsonResponse
    {
        try {
            $result = $this->consultationService->searchDoctors($data);
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Error searching for doctors.', [
                'search_term' => $data,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'value' => false,
                'message' => 'Failed to search for doctors.',
            ], 500);
        }
    }
}
