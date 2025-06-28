<?php

namespace App\Modules\Recommendations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recommendations\Services\RecommendationService;
use App\Modules\Recommendations\Requests\StoreRecommendationRequest;
use App\Modules\Recommendations\Requests\UpdateRecommendationRequest;
use App\Modules\Recommendations\Requests\DeleteRecommendationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    /**
     * The recommendation service instance.
     *
     * @var RecommendationService
     */
    protected $recommendationService;

    /**
     * Create a new controller instance.
     *
     * @param RecommendationService $recommendationService
     */
    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get all recommendations for a patient.
     *
     * @param int $patientId
     * @return JsonResponse
     */
    public function index(int $patientId): JsonResponse
    {
        try {
            $recommendations = $this->recommendationService->getPatientRecommendations($patientId);
            
            if (!$recommendations['value']) {
                return response()->json($recommendations, 404);
            }
            
            return response()->json($recommendations, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch recommendations', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Failed to fetch recommendations'
            ], 500);
        }
    }

    /**
     * Store new recommendations for a patient.
     *
     * @param StoreRecommendationRequest $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function store(StoreRecommendationRequest $request, int $patientId): JsonResponse
    {
        try {
            $recommendations = $this->recommendationService->createRecommendations(
                $patientId,
                $request->recommendations
            );
            
            if (!$recommendations['value']) {
                return response()->json($recommendations, 404);
            }
            
            return response()->json($recommendations, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create recommendations', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Failed to create recommendations'
            ], 500);
        }
    }

    /**
     * Update recommendations for a patient.
     *
     * @param UpdateRecommendationRequest $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function update(UpdateRecommendationRequest $request, int $patientId): JsonResponse
    {
        try {
            $recommendations = $this->recommendationService->updateRecommendations(
                $patientId,
                $request->recommendations
            );
            
            if (!$recommendations['value']) {
                return response()->json($recommendations, 404);
            }
            
            return response()->json($recommendations, 200);
        } catch (\Exception $e) {
            Log::error('Failed to update recommendations', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Failed to update recommendations'
            ], 500);
        }
    }

    /**
     * Delete recommendations for a patient.
     *
     * @param DeleteRecommendationRequest $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function destroy(DeleteRecommendationRequest $request, int $patientId): JsonResponse
    {
        try {
            $result = $this->recommendationService->deleteRecommendations($patientId, $request->ids);
            
            if (!$result['value']) {
                return response()->json($result, 404);
            }
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete recommendations', [
                'patient_id' => $patientId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Failed to delete recommendations'
            ], 500);
        }
    }
}
