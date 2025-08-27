<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Controllers\ChatController as ModuleChatController;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatController;

    public function __construct(ModuleChatController $chatController)
    {
        $this->chatController = $chatController;
    }

    public function sendConsultation(Request $request, $patientId)
    {
        return $this->chatController->sendConsultation($request, $patientId);
    }

    public function getConsultationHistory($patientId)
    {
        return $this->chatController->getConsultationHistory($patientId);
    }
}
