<?php

namespace App\Http\Controllers;

use App\Services\ChatGPTService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected $chatGPTService;

    public function __construct(ChatGPTService $chatGPTService)
    {
        $this->chatGPTService = $chatGPTService;
    }

    public function chat(Request $request)
    {
        $message = $request->input('message');

        // Send message to ChatGPT service and get the response
        $response = $this->chatGPTService->sendMessage($message);

        // Return the response to the view or API
        return response()->json($response);
    }
}

