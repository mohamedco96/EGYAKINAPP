<?php

// ChatController.php
namespace App\Http\Controllers;

use App\Models\AIConsultation;
use App\Models\User;
use App\Models\SectionsInfo;
use App\Models\Questions;
use App\Models\DoctorMonthlyTrial;
use App\Services\ChatGPTService;
use Carbon\Carbon;
use Illuminate\Http\Request;
//import the Auth facade
use Illuminate\Support\Facades\Auth;
//import the Patients model
use App\Models\Patients;
use Illuminate\Support\Facades\Log;


class ChatController extends Controller
{
    protected $chatGPTService;

    public function __construct(ChatGPTService $chatGPTService)
    {
        $this->chatGPTService = $chatGPTService;
    }

    // 1. Consultation request endpoint
    /**
     * Handle sending a consultation request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendConsultation($patient_id)
    {
        // Retrieve the patient from the database with related data
        $patient = Patients::with(['doctor', 'status', 'answers'])->findOrFail($patient_id);

        // Check if the patient exists
        if (!$patient) {
            \Log::error('Patient not found', [
                'doctor_id' => $doctor_id,
                'patient_id' => $patient_id
            ]);
            return response()->json([
                'value' => false,
                'message' => 'Patient not found'
            ], 404);
        }

        $sections_infos = SectionsInfo::all();
        $data = [];

        // Fetch all questions
        $questions = Questions::with('section')->get();

        // Iterate over each question
        foreach ($questions as $question) {
            // Skip questions with certain IDs
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");
                continue;
            }

            $questionData = [
                'id' => $question->id,
                'section_id' => $question->section->id ?? "test",
                'section_name' => $question->section->section_name ?? "Not found",
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];

            // Find the answer for this question
            $answer = $patient->answers->where('question_id', $question->id)->first();

            if ($question->type === 'multiple') {
                // Initialize the answer array
                $questionData['answer'] = [
                    'answers' => [], // Initialize answers as an empty array
                    'other_field' => null // Set other_field to null by default
                ];

                // Find answers for this question
                $questionAnswers = $patient->answers->where('question_id', $question->id);

                // Populate the answers array
                foreach ($questionAnswers as $answer) {
                    if ($answer->type !== 'other') {
                        $questionData['answer']['answers'][] = $answer->answer;
                    }
                    if ($answer->type === 'other') {
                        $questionData['answer']['other_field'] = $answer->answer;
                    }
                }
            } else {
                // For other types of questions, return the answer directly
                $questionData['answer'] = $answer ? $answer->answer : null;
            }

            $data[] = $questionData;
        }

        // Pass the data to the blade view
        $patientData = [
            'patient' => $patient,
            'questionData' => $data,
            'sections_infos' => $sections_infos
            // Add more data here if needed
        ];

        // Generate the prompt using the patient's data
        $prompt = $this->chatGPTService->generatePrompt($patientData);

        // Retrieve the doctor and question from the request
        $doctor_id = Auth::id();

        // Check if the doctor has trials left
        $trial = DoctorMonthlyTrial::firstOrCreate(
            ['doctor_id' => $doctor_id],
            ['trial_count' => 3, 'reset_date' => now()->addMonth()]
        );

        // Reset trial count if the reset date has passed
        if (Carbon::now()->greaterThanOrEqualTo($trial->reset_date)) {
            $trial->update(['trial_count' => 3, 'reset_date' => now()->addMonth()]);
        }

        // If no trials left, return an error response
        if ($trial->trial_count <= 0) {
            \Log::warning('No trials left for this month', [
                'doctor_id' => $doctor_id,
                'patient_id' => $patient_id
            ]);
            return response()->json([
                'value' => false,
                'message' => 'No trials left for this month'
            ], 403);
        }

        // Send the question to ChatGPT
        $response = $this->chatGPTService->sendMessage($prompt);
        // $response = "This is a dummy response from ChatGPT.\n\nThis is a dummy response from ChatGPT.\nThis is a dummy response from ChatGPT.";

        // Save the consultation in the database
        $consultation = AIConsultation::create([
            'doctor_id' => $doctor_id,
            'patient_id' => $patient_id,
            'question' => $prompt,
            'response' => $response
        ]);

        // Decrement trial count
        $trial->decrement('trial_count');

        // Log the consultation request
        \Log::info('Consultation request sent', [
            'doctor_id' => $doctor_id,
            'patient_id' => $patient_id,
            'question' => $prompt,
            'response' => $response,
            'trial_count' => $trial->trial_count
        ]);

        // Return the AI response
        return response()->json([
            'value' => true,
            'message' => 'Consultation request sent successfully',
            // 'question' => $prompt,
            'response' => $response,
            'trial_count' => $trial->trial_count
        ]);
    }

    // 2. Consultation history endpoint
    /**
     * Retrieve consultation history for a patient.
     *
     * @param int $patientId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConsultationHistory($patientId)
    {
        $doctor_id = Auth::id();

        // Check and reset the trial count if the reset date has passed
        $trial = DoctorMonthlyTrial::firstOrCreate(
            ['doctor_id' => $doctor_id],
            ['trial_count' => 3, 'reset_date' => now()->addMonth()]
        );

        if (Carbon::now()->greaterThanOrEqualTo($trial->reset_date)) {
            $trial->update(['trial_count' => 3, 'reset_date' => now()->addMonth()]);
        }

        // Retrieve consultation history for the patient, sorted by newest
        $history = AIConsultation::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->select('id', 'doctor_id', 'patient_id', 'response', 'created_at')
            ->paginate(5);

        // Log the retrieval of consultation history
        \Log::info('AI Consultation history retrieved', [
            'doctor_id' => $doctor_id,
            'patient_id' => $patientId,
            'trial_count' => $trial->trial_count,
            'history_count' => $history->count()
        ]);

        return response()->json([
            'value' => true,
            'message' => 'Consultation history retrieved successfully',
            'trial_count' => $trial->trial_count,
            'reset_date' => $trial->reset_date,
            'history' => $history
        ]);
    }
}
