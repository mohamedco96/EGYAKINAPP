<?php

namespace App\Modules\Consultations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Consultations\Requests\AddDoctorsToConsultationRequest;
use App\Modules\Consultations\Requests\StoreConsultationRequest;
use App\Modules\Consultations\Requests\ToggleConsultationStatusRequest;
use App\Modules\Consultations\Requests\UpdateConsultationRequest;
use App\Modules\Consultations\Services\ConsultationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for doctors.',
            ], 500);
        }
    }

    /**
     * Add new doctors to an existing consultation.
     */
    public function addDoctors(AddDoctorsToConsultationRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->addDoctorsToConsultation($id, $request->validated());

            $statusCode = $result['value'] ? 200 : 400;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error adding doctors to consultation.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to add doctors to consultation.',
            ], 500);
        }
    }

    /**
     * Toggle consultation open/close status.
     */
    public function toggleStatus(ToggleConsultationStatusRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->toggleConsultationStatus($id, $request->validated());

            $statusCode = $result['value'] ? 200 : 400;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error toggling consultation status.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to update consultation status.',
            ], 500);
        }
    }

    /**
     * Get consultation members.
     */
    public function getMembers(int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->getConsultationMembers($id);

            $statusCode = $result['value'] ? 200 : 400;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error getting consultation members.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to get consultation members.',
            ], 500);
        }
    }

    /**
     * Add a new reply to consultation (allows multiple replies).
     */
    public function addReply(UpdateConsultationRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->consultationService->addConsultationReply($id, $request->validated());

            $statusCode = $result['value'] ? 201 : 400;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error adding consultation reply.', [
                'consultation_id' => $id,
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to add reply.',
            ], 500);
        }
    }

    /**
     * Remove a doctor from consultation.
     */
    public function removeDoctor(int $consultationId, int $doctorId): JsonResponse
    {
        try {
            $result = $this->consultationService->removeDoctorFromConsultation($consultationId, $doctorId);

            $statusCode = $result['value'] ? 200 : 400;

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error removing doctor from consultation.', [
                'consultation_id' => $consultationId,
                'doctor_id' => $doctorId,
                'auth_doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to remove doctor from consultation.',
            ], 500);
        }
    }
}
