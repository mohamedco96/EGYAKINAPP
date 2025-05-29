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
            // $apiKey = $this->apiKey;
            $apiKey = config('services.openai.api_key');
            
            Log::info('API Key: ' . $apiKey);
    
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4', // Adjust to the correct model if needed
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
                Log::error('ChatGPT API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'headers' => $response->headers()
                ]);
                return 'Error: Unable to get a response from ChatGPT.';
            }
        } catch (\Exception $e) {
            // Log exception message
            Log::error('ChatGPT Service Exception', ['message' => $e->getMessage()]);
            return 'Error: An exception occurred while communicating with ChatGPT.';
        }
    }
    

    public function generatePrompt($patientData)
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
        foreach ($patientData['patient']->answers as $answer) {
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
    
        // Start constructing the initial prompt
        $prompt = sprintf(
            "I am a nephrologist who had the following case:
            Summarize in a table the possible differential diagnosis with the best next step for each one? At the end of the table mention your most probable diagnosis.
            Revise my management plan and add your suggestions" .
            "Patient Information: %s Patient Named %s Aged %s From %s - %s, " .
            "Hospital: %s, " .
            "His special habit: %s %s, " .
            "DM: %s, " .
            "HTN: %s, " .
            "Complaint: %s. ",
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
    
        // Define the URL prefix for file links
        $URLprefix = "https://api.egyakin.com/storage/";
    
        // Append dynamic sections to the prompt
        foreach ($patientData['sections_infos'] as $sections_info) {
            if (!in_array($sections_info->id, [])) { // Skip sections with IDs 1, 6, and 8
                $sectionText = "\nSection: " . $sections_info->section_name . "\n";
                $hasAnsweredQuestion = false;
    
                foreach ($patientData['questionData'] as $data) {
                    if (
                        $data['section_id'] === $sections_info->id &&
                        !is_null($data['answer']) &&
                        (
                            (is_array($data['answer']['answers'] ?? null) && count($data['answer']['answers']) > 0) ||
                            !isset($data['answer']['answers'])
                        )
                    ) {
                        $hasAnsweredQuestion = true;
                        $sectionText .= "Q: " . $data['question'] . "\n";
    
                        if ($data['type'] === 'multiple') {
                            // Concatenate answers into a single string
                            // $answers = is_array($data['answer']['answers']) ? implode(', ', $data['answer']['answers']) : $data['answer'];
                            // $sectionText .= "A: " . $answers . "\n";
                            // if (isset($data['answer']['other_field'])) {
                            //     $sectionText .= "Others: " . $data['answer']['other_field'] . "\n";
                            // }
                        } elseif ($data['type'] === 'files') {
                            // Decode JSON string and handle each file path
                            // $filePaths = json_decode($data['answer'], true);
                            // if (is_array($filePaths)) {
                            //     foreach ($filePaths as $filePath) {
                            //         $sectionText .= "File: " . $URLprefix . $filePath . "\n";
                            //     }
                            // }
                        } else {
                            $sectionText .= "A: " . $data['answer'] . "\n";
                        }
                    }
                }
    
                // If no questions with answers exist, add placeholder text
                if (!$hasAnsweredQuestion) {
                    $sectionText .= "No information available.\n";
                }
    
                // Append the section text to the main prompt
                $prompt .= $sectionText;
            }
        }
    
        return $prompt;
    }
    
    
    
    
    
}
