<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\ChatController as V1ChatController;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatController;

    public function __construct(V1ChatController $chatController)
    {
        $this->chatController = $chatController;
    }

    public function sendConsultation(Request $request, $patientId)
    {
        return $this->chatController->sendConsultation($patientId);
    }

    public function getConsultationHistory($patientId)
    {
        return $this->chatController->getConsultationHistory($patientId);
    }
}
