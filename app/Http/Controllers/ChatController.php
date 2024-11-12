<?php

// ChatController.php
namespace App\Http\Controllers;

use App\Models\AIConsultation;
use App\Models\User;
use App\Models\DoctorMonthlyTrial;
use App\Services\ChatGPTService;
use Carbon\Carbon;
use Illuminate\Http\Request;
//import the Auth facade
use Illuminate\Support\Facades\Auth;
//import the Patients model
use App\Models\Patients;

class ChatController extends Controller {
    protected $chatGPTService;

    public function __construct(ChatGPTService $chatGPTService) {
        $this->chatGPTService = $chatGPTService;
    }

    // 1. Consultation request endpoint
    /**
     * Handle sending a consultation request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendConsultation($patient_id) {

            // Retrieve patient data as before
    $patient = Patients::with(['doctor', 'status', 'answers'])->findOrFail($patient_id);

            // Check if the patient exists
        //$patient = Patients::find($patient_id);
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

    // Generate the prompt using the patient's data
    $prompt = $this->chatGPTService->generatePrompt($patient);

        // Retrieve the doctor and question from the request
        $doctor_id = Auth::id();
        //$question = "This is a dummy question for testing.";

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
        // $response = $this->chatGPTService->sendMessage($prompt);

        $response = "This is a dummy response from ChatGPT.";

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
            'question' => $prompt,
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
    public function getConsultationHistory($patientId) {
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
                    ->get();

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
