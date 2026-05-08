<?php

namespace App\Modules\Chat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Services\ChatService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle sending a consultation request.
     */
    public function sendConsultation(int $patientId): JsonResponse
    {
        $result = $this->chatService->sendConsultation($patientId);

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Retrieve consultation history for a patient.
     */
    public function getConsultationHistory(int $patientId): JsonResponse
    {
        $result = $this->chatService->getConsultationHistory($patientId);

        return response()->json($result['data'], $result['status_code']);
    }
}
