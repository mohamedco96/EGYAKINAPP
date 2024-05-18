<?php

namespace App\Http\Controllers;

use App\Models\Patients;
use App\Http\Requests\UpdatePatientsRequest;
use App\Models\PatientStatus;
use App\Models\Questions;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\SectionsInfo;
use App\Models\Answers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class SectionsController extends Controller
{
    protected $Patients;

    public function __construct(Patients $Patients)
    {
        $this->Patients = $Patients;
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateFinalSubmit(UpdatePatientsRequest $request, $patient_id)
    {
        $patientSubmitStatus = PatientStatus::where('patient_id', $patient_id)
            ->where('key', 'submit_status')->first();

        if (!$patientSubmitStatus) {
            $response = [
                'value' => false,
                'message' => 'Patient not found',
            ];

            return response()->json($response, 404);
        }

        // Update submit status
        $patientSubmitStatus->update(['status' => true]);

        // Scoring system
        $doctorId = Auth::id();
        $incrementAmount = 4;
        $action = 'Final Submit';

        $score = Score::firstOrNew(['doctor_id' => $doctorId]);
        $score->score += $incrementAmount;
        $score->threshold += $incrementAmount;
        $newThreshold = $score->threshold;

        // Send notification if the new score exceeds 50 or its multiples
        if ($newThreshold >= 50) {
            // Load user object
            $user = Auth::user();
            // Send notification
            $user->notify(new ReachingSpecificPoints($score));
            $score->threshold = 0;
        }

        $score->save();

        // Log score history
        ScoreHistory::create([
            'doctor_id' => $doctorId,
            'score' => $incrementAmount,
            'action' => $action,
            'timestamp' => now(),
        ]);

        $response = [
            'value' => true,
            'message' => 'Final Submit Updated Successfully',
        ];

        return response()->json($response, 201);
    }

    /**
     * Show questions and answers for a specific section and patient.
     */

    public function showQuestionsAnswers($section_id, $patient_id)
    {
        try {
            // Check if the section exists
            $sectionExists = Questions::where('section_id', $section_id)->exists();
            if (!$sectionExists) {
                return response()->json([
                    'value' => false,
                    'message' => "Section not found",
                ], 404);
            }

            $patient = Patients::find($patient_id); // Retrieve the patient from the database
            if (!$patient) {
                return response()->json([
                    'value' => false,
                    'message' => "Patient not found",
                ], 404);
            }

            $data = [];

            // Fetch questions dynamically based on section_id
            $questions = Questions::where('section_id', $section_id)
                ->orderBy('id')
                ->get();

            // Fetch all answers for the patient in one query
            $answers = Answers::where('patient_id', $patient_id)
                ->get();

            foreach ($questions as $question) {
                // Skip questions with certain IDs
                if ($question->skip) {
                    Log::info("Question with ID {$question->id} skipped as per skip flag.");
                    continue;
                }

                $questionData = [
                    'id' => $question->id,
                    'question' => $question->question,
                    'values' => $question->values,
                    'type' => $question->type,
                    'keyboard_type' => $question->keyboard_type,
                    'mandatory' => $question->mandatory,
                    'updated_at' => $question->updated_at,
                ];

                // Find the answer for this question from the fetched answers
                $answer = $answers->where('question_id', $question->id)->first();

                // Get the IDs of questions of type 'multiple'
                $multipleQuestionIds = $questions->filter(function ($question) {
                    return $question->type === 'multiple';
                })->pluck('id')->toArray();

                $multipleQuestionAnswers = Answers::whereIn('question_id', $multipleQuestionIds)
                    ->where('patient_id', $patient_id)
                    ->get();

                if ($question->type === 'multiple') {
                    // Initialize the answer array
                    $questionData['answer'] = [
                        'answers' => [], // Initialize answers as an empty array
                        'other_field' => "" // Set other_field to null by default
                    ];
                    // Find answers for this question from the fetched answers
                    $questionAnswers = $multipleQuestionAnswers->where('question_id', $question->id);

                    // Populate the answers array
                    foreach ($questionAnswers as $answer) {
                        if ($answer->type !== 'other') {
                            $questionData['answer']['answers'] = $answer->answer;
                        }
                        if ($answer->type === 'other') {
                            $questionData['answer']['other_field'] = $answer->answer;
                        }
                    }
                } else {
                    // For other types of questions, return the answer directly
                    $questionData['answer'] = $answer ? $answer->answer : "";
                }

                $data[] = $questionData;
            }

            $response = [
                'value' => true,
                'data' => $data,
            ];

            Log::info("Questions and answers retrieved successfully for section ID {$section_id} and patient ID {$patient_id}.");

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error("Error while fetching questions and answers: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function showSections($patient_id)
    {
        $submit_status = PatientStatus::where('patient_id', $patient_id)
            ->where('key', 'submit_status')
            ->value('status');


        $sections = PatientStatus::select('key', 'status', 'updated_at')
            ->where('patient_id', $patient_id)
            ->where('key', 'LIKE', 'section_%')
            ->get();

        $patient_name = Answers::where('patient_id', $patient_id)
            ->where('question_id', '1')
            ->value('answer');

        $doctor_Id = Patients::where('id', $patient_id)->value('doctor_id');

        if (!$patient_name) {
            return response()->json([
                'value' => false,
                'message' => 'Patient not found for the given patient ID.',
            ], 404);
        }

        if (!$sections) {
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }

        $sectionInfos = SectionsInfo::all();

        $data = [];
        foreach ($sectionInfos as $sectionInfo) {
            if ($sectionInfo->id === 8) {
                continue;
            }
            $section_id = $sectionInfo->id;
            $section_name = $sectionInfo->section_name;

            $section_data = $sections->firstWhere('key', 'section_' . $section_id);

            if (!$section_data) {
                $updated_at_value = null;
                $section_status = false;
            } else {
                $updated_at_value = $section_data->updated_at;
                $section_status = $section_data->status;
            }


            $section = [
                'section_id' => $section_id,
                'section_status' => $section_status,
                'updated_at' => $updated_at_value,
                'section_name' => $section_name,
            ];

            $data[] = $section;
        }

        if ($sections) {
            return response()->json([
                'value' => true,
                'submit_status' => $submit_status,
                'patient_name' => $patient_name,
                'doctor_Id' => $doctor_Id,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }
    }

}
