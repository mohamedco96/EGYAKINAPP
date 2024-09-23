<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionsRequest;
use App\Http\Requests\UpdateQuestionsRequest;
use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\Outcome;
use App\Models\PatientHistory;
use App\Models\Questions;
use App\Models\Risk;
use App\Models\SectionFieldMapping;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Log;

class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = Questions::all();

        if ($questions->isEmpty()) {
            Log::warning("No questions found.");
            return Response::json(['value' => false, 'message' => 'No questions found.'], 404);
        }

        return Response::json(['value' => true, 'data' => $questions], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionsRequest $request)
    {
        $questions = Questions::create($request->all());

        Log::info("Question stored successfully. ID: {$questions->id}");

        return Response::json(['value' => true, 'data' => $questions], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($section_id)
    {
        // Fetch questions dynamically based on section_id
        $questions = Questions::where('section_id', $section_id)
            ->orderBy('sort')
            ->get();

        // Check if questions are found
        if ($questions->isEmpty()) {
            Log::warning("No questions found for section ID: {$section_id}");
            return response()->json([
                'value' => false,
                'message' => 'No questions found for the given section ID.',
            ], 404);
        }

        $data = [];
        foreach ($questions as $question) {
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");
                continue;
            }
            // Construct question data
            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];

            // Add question data to the response data
            $data[] = $questionData;
        }

        // Prepare the response
        $response = [
            'value' => true,
            'data' => $data,
        ];

        // Log success message
        Log::info("Questions retrieved successfully for section ID: {$section_id}");

        // Return the response
        return response()->json($response, 200);
    }

    /**
     * Display questions and answers for a specific section and patient.
     */
    public function ShowQuestitionsAnswars($section_id, $patient_id)
    {
        $data = [];

        // Fetch questions dynamically based on section_id
        $questions = Questions::where('section_id', $section_id)
            ->orderBy('sort')
            ->get();

        foreach ($questions as $question) {
            // Skip questions with certain IDs
            if ($question->skip) {
                Log::info("Question with ID {$question->id} skipped as per skip flag.");
                continue;
            }

            $answersModel = $this->getAnswersModel($section_id);
            if (!$answersModel) {
                Log::warning("No answer model found for section ID {$section_id}.");
                continue;
            }

            // Adjust patient_id column based on section_id
            $patientIdColumn = $section_id == 1 ? 'id' : 'patient_id';
            $answers = $answersModel::where($patientIdColumn, $patient_id)->first();

            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];

            // Get the main answer column name dynamically
            $mainAnswerColumnName = $this->getAnswerColumnName($question->id);

            // Construct the other field column name by appending '_other_field' to the main answer column name
            $otherFieldColumnName = $mainAnswerColumnName . '_other_field';

            if ($question->type === 'multiple') {
                $questionData['answer'] = [
                    'answers' => $answers->$mainAnswerColumnName ?? null,
                    'other_field' => $answers->$otherFieldColumnName ?? null,
                ];
            } else {
                $questionData['answer'] = $answers->$mainAnswerColumnName ?? null;
            }

            $data[] = $questionData;
        }

        $response = [
            'value' => true,
            'data' => $data,
        ];

        Log::info("Questions and answers retrieved successfully for section ID {$section_id} and patient ID {$patient_id}.");

        return response()->json($response, 200);
    }

    private function getAnswersModel($section_id)
    {
        switch ($section_id) {
            case 1:
                return PatientHistory::class;
            case 2:
                return Complaint::class;
            case 3:
                return Cause::class;
            case 4:
                return Risk::class;
            case 5:
                return Assessment::class;
            case 6:
                return Examination::class;
            case 7:
                return Decision::class;
            case 8:
                return Outcome::class;
            default:
                return null;
        }
    }

    private function getAnswerColumnName($question_id)
    {
        // Fetch column name from SectionFieldMapping model
        $columnName = SectionFieldMapping::where('field_name', $question_id)
            ->value('column_name');

        return $columnName ? $columnName : 'column_' . $question_id;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionsRequest $request, $id)
    {
        $questions = Questions::find($id);

        if (!$questions) {
            Log::warning("No questions found for update. ID: {$id}");
            return Response::json(['value' => false, 'message' => 'No questions found.'], 404);
        }

        $questions->update($request->all());

        Log::info("Questions updated successfully. ID: {$id}");

        return Response::json(['value' => true, 'data' => $questions, 'message' => 'Questions updated successfully.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $questions = Questions::find($id);

        if (!$questions) {
            Log::warning("No questions found for deletion. ID: {$id}");
            return Response::json(['value' => false, 'message' => 'No questions found.'], 404);
        }

        $questions->delete();

        Log::info("Questions deleted successfully. ID: {$id}");

        return Response::json(['value' => true, 'message' => 'Questions deleted successfully.'], 200);
    }
}
