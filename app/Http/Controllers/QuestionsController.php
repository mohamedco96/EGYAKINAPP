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


class QuestionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $questions = Questions::all();

        if (!$questions) {
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

        return Response::json(['value' => true, 'data' => $questions], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($section_id)
    {
        $questions = Questions::where('section_id', $section_id)
            ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
            ->get();

        if ($questions->isEmpty()) {
            return response()->json([
                'value' => false,
                'message' => 'No questions found for the given section ID.',
            ], 404);
        }

        $data = [];
        foreach ($questions as $question) {
            $data[] = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
            ];
        }

        $response = [
            'value' => true,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


    public function ShowQuestitionsAnswars($section_id, $patient_id)
    {
        $data = [];

        // Fetch questions dynamically based on section_id
        $questions = Questions::where('section_id', $section_id)
            ->orderBy('id')
            ->get();

        foreach ($questions as $question) {
            // Skip questions with certain IDs
            if (in_array($question->id, [15, 25, 28, 30, 58, 60, 64, 67, 69])) {
                continue;
            }

            $answersModel = $this->getAnswersModel($section_id);
            if (!$answersModel) {
                continue;
            }

            // Adjust patient_id column based on section_id
            $patientIdColumn = $section_id == 1 ? 'id' : 'patient_id';
            $answers = $answersModel::where($patientIdColumn, $patient_id)->first();

            $answerColumn = $this->getAnswerColumnName($question->id);
            //echo $answerColumn . "\n";
            $questionData = [
                'id' => $question->id,
                'question' => $question->question,
                'values' => $question->values,
                'type' => $question->type,
                'keyboard_type' => $question->keyboard_type,
                'mandatory' => $question->mandatory,
                'updated_at' => $question->updated_at,
                'answer' => isset($answers->$answerColumn) ? $answers->$answerColumn : null,
                //'answer' => [
                   // 'answers' => isset($answers->special_habits_of_the_patient) ? $answers->special_habits_of_the_patient : null,
                    //'other_field' => isset($answers->other_habits_of_the_patient) ? $answers->other_habits_of_the_patient : null,
                //],
            ];

            $data[] = $questionData;
        }

        $response = [
            'value' => true,
            'data' => $data,
        ];

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
        //echo $columnName;
        return $columnName ? $columnName : 'column_' . $question_id;


    }



    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionsRequest $request, $id)
    {
        $questions = Questions::find($id);

        if (!$questions) {
            return Response::json(['value' => false, 'message' => 'No questions found.'], 404);
        }

        $questions->update($request->all());

        return Response::json(['value' => true, 'data' => $questions, 'message' => 'Questions updated successfully.'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $questions = Questions::find($id);

        if (!$questions) {
            return Response::json(['value' => false, 'message' => 'No questions found.'], 404);
        }

        $questions->delete();

        return Response::json(['value' => true, 'message' => 'Questions deleted successfully.'], 200);
    }
}
