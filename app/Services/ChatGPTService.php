<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function sendMessage($message)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo', // Specify the model, adjust to GPT-4 if needed
                'messages' => [
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

            // Check if the request was successful
            if ($response->successful()) {
                // Extract the actual content of the response
                $content = $response->json('choices.0.message.content');
                return $content;
            } else {
                // Log error details
                Log::error('ChatGPT API Error', ['status' => $response->status(), 'body' => $response->body()]);
                return 'Error: Unable to get a response from ChatGPT.';
            }
        } catch (\Exception $e) {
            // Log exception message
            Log::error('ChatGPT Service Exception', ['message' => $e->getMessage()]);
            return 'Error: An exception occurred while communicating with ChatGPT.';
        }
    }

    public function generatePrompt($patient)
    {
        // Initialize placeholders with default values
        $patientName = null;
        $hospital = null;
        $patientGender = null;
        $patientAge = null;
        $patientHabit = null;
        $patientHabitOther = null;
        $patientDM = null;
        $patientHTN = null;
        $governorate = null;
        $maritalStatus = null;
        $complaintText = "None"; // Default complaint
    
        // Extract patient data from answers
        foreach ($patient->answers as $answer) {
            switch ($answer['question_id']) {
                case "1":
                    $patientName = $answer['answer'];
                    break;
                case "2":
                    $hospital = $answer['answer'];
                    break;
                case "8":
                    $patientGender = $answer['answer'];
                    break;
                case "7":
                    $patientAge = $answer['answer'];
                    break;
                case "14":
                    if (!isset($answer['type'])) {
                        $patientHabit = is_array($answer['answer']) ? implode(', ', $answer['answer']) : $answer['answer'];
                    } elseif ($answer['type'] === 'other') {
                        $patientHabitOther = $answer['answer'];
                    }
                    break;
                case "16":
                    $patientDM = $answer['answer'];
                    break;
                case "18":
                    $patientHTN = $answer['answer'];
                    break;
                case "11":
                    $governorate = $answer['answer'];
                    break;
                case "12":
                    $maritalStatus = $answer['answer'];
                    break;
                case "24": // Assuming 24 is the question_id for Complaint
                    if ($answer['type'] === 'multiple') {
                        $complaintText = implode(', ', $answer['answers'] ?? []);
                        if (isset($answer['other_field'])) {
                            $complaintText .= " and " . $answer['other_field'];
                        }
                    } else {
                        $complaintText = $answer['answer'];
                    }
                    break;
            }
        }
    
        // Format the prompt as a single block of text, including Complaint
        $prompt = sprintf(
            "I am a nephrologist who had the following case: ".
            "Patient Information: %s Patient Named %s Aged %s From %s - %s, " .
            "Hospital: %s, " .
            "His special habit: %s %s, " .
            "DM: %s, " .
            "HTN: %s, " .
            "Complaint: %s, " .
            "Summarize in a table the possible differential diagnosis with the best next step for each one. At the end of the table mention your most probable diagnosis. Revise my management plan and add your suggestions. " . 
            "return the response in a table format html code",
            $patientGender ?? 'Unknown',
            $patientName ?? 'Unknown',
            $patientAge ?? 'Unknown',
            $governorate ?? 'Unknown',
            $maritalStatus ?? 'Unknown',
            $hospital ?? 'Unknown',
            $patientHabit ?? 'None',
            $patientHabitOther ? "and {$patientHabitOther}" : "",
            $patientDM ?? 'None',
            $patientHTN ?? 'None',
            $complaintText
        );
    
        return $prompt;
    }
    
    
}
