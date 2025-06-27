<?php

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle sending a consultation request.
     *
     * @param int $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendConsultation(int $patientId): JsonResponse
    {
        $result = $this->chatService->sendConsultation($patientId);
        
        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Retrieve consultation history for a patient.
     *
     * @param int $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConsultationHistory(int $patientId): JsonResponse
    {
        $result = $this->chatService->getConsultationHistory($patientId);
        
        return response()->json($result['data'], $result['status_code']);
    }
}
