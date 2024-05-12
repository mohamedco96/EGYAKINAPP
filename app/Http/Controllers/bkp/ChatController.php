<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $apiKey = '';
        $endpoint = 'https://api.openai.com/v1/completions';
        $client = new Client();

        $response = $client->post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => 'davinci-002', // Use a supported model
                'prompt' => $request->input('message'),
                'temperature' => 0.7,
                'max_tokens' => 150,
                'top_p' => 1,
                'stop' => ['\n'],
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        $message = $responseBody['choices'][0]['text'];

        return response()->json(['message' => $message]);
    }
}
