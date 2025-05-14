<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecommendationRequest;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $recommendations = $this->recommendationService->getPatientRecommendations($patientId);
        
        if (!$recommendations['value']) {
            return response()->json($recommendations, 404);
        }
        
        return response()->json($recommendations, 200);
    }

    /**
     * Store new recommendations for a patient.
     *
     * @param RecommendationRequest $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function store(RecommendationRequest $request, int $patientId): JsonResponse
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
     * @param RecommendationRequest $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function update(RecommendationRequest $request, int $patientId): JsonResponse
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
     * @param Request $request
     * @param int $patientId
     * @return JsonResponse
     */
    public function destroy(Request $request, int $patientId): JsonResponse
    {
        $validator = validator($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->recommendationService->deleteRecommendations($patientId, $request->ids);
            
            if (!$result['value']) {
                return response()->json($result, 404);
            }
            
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json([
                'value' => false,
                'data' => null,
                'message' => 'Failed to delete recommendations'
            ], 500);
        }
    }
} 