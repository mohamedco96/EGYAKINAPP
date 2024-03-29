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
        switch ($section_id) {
            case 1:
                $data = [];
                for ($i = 1; $i <= 20; $i++) {
                    // Skip iteration when $i is 15
                    if ($i === 15) {
                        continue;
                    }
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = PatientHistory::where('id', $patient_id)
                        ->select('id', 'name', 'hospital', 'collected_data_from', 'NID', 'phone', 'email', 'age', 'gender', 'occupation',
                            'residency', 'governorate', 'marital_status', 'educational_level', 'special_habits_of_the_patient', 'other_habits_of_the_patient', 'DM',
                            'DM_duration', 'HTN', 'HTN_duration', 'other', )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 1:
                            $question['answer'] = $answers->{'name'};
                            break;
                        case 2:
                            $question['answer'] = $answers->{'hospital'};
                            break;
                        case 3:
                            $question['answer'] = $answers->{'collected_data_from'};
                            break;
                        case 4:
                            $question['answer'] = $answers->{'NID'};
                            break;
                        case 5:
                            $question['answer'] = $answers->{'phone'};
                            break;
                        case 6:
                            $question['answer'] = $answers->{'email'};
                            break;
                        case 7:
                            $question['answer'] = $answers->{'age'};
                            break;
                        case 8:
                            $question['answer'] = $answers->{'gender'};
                            break;
                        case 9:
                            $question['answer'] = $answers->{'occupation'};
                            break;
                        case 10:
                            $question['answer'] = $answers->{'residency'};
                            break;
                        case 11:
                            $question['answer'] = $answers->{'governorate'};
                            break;
                        case 12:
                            $question['answer'] = $answers->{'marital_status'};
                            break;
                        case 13:
                            $question['answer'] = $answers->{'educational_level'};
                            break;
                        case 14:
                            $question['answer'] = [
                                'answers' => $answers->{'special_habits_of_the_patient'},
                                'other_field' => $answers->{'other_habits_of_the_patient'},
                            ];
                            break;
                        case 16:
                            $question['answer'] = $answers->{'DM'};
                            break;
                        case 17:
                            $question['answer'] = $answers->{'DM_duration'};
                            break;
                        case 18:
                            $question['answer'] = $answers->{'HTN'};
                            break;
                        case 19:
                            $question['answer'] = $answers->{'HTN_duration'};
                            break;
                        case 20:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }

                    $data[] = $question;
                }
                break;
            case 2:
                $data = [];
                for ($i = 21; $i <= 25; $i++) {
                    if ($i === 25) {
                        continue;
                    }
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Complaint::where('patient_id', $patient_id)
                        ->select('id', 'where_was_th_patient_seen_for_the_first_time', 'place_of_admission',
                            'date_of_admission', 'main_omplaint', 'other')
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 21:
                            $question['answer'] = $answers->{'where_was_th_patient_seen_for_the_first_time'};
                            break;
                        case 22:
                            $question['answer'] = $answers->{'place_of_admission'};
                            break;
                        case 23:
                            $question['answer'] = $answers->{'date_of_admission'};
                            break;
                        case 24:
                            $question['answer'] = [
                                'answers' => $answers->{'main_omplaint'},
                                'other_field' => $answers->{'other'},
                            ];
                            break;
                    }

                    $data[] = $question;
                }
                break;
            case 3:
                $data = [];
                for ($i = 26; $i <= 33; $i++) {
                    if ($i === 28 || $i === 30) {
                        continue;
                    }
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Cause::where('patient_id', $patient_id)
                        ->select('id',
                            'cause_of_AKI', 'pre-renal_causes', 'pre-renal_others', 'renal_causes', 'renal_others', 'post-renal_causes', 'post-renal_others', 'other'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 26:
                            $question['answer'] = $answers->{'cause_of_AKI'};
                            break;
                        case 27:
                            $question['answer'] = [
                                'answers' => $answers->{'pre-renal_causes'},
                                'other_field' => $answers->{'pre-renal_others'},
                            ];
                            break;
                        case 29:
                            $question['answer'] = [
                                'answers' => $answers->{'renal_causes'},
                                'other_field' => $answers->{'renal_others'},
                            ];
                            break;
                        case 31:
                            $question['answer'] = $answers->{'post-renal_causes'};
                            break;
                        case 32:
                            $question['answer'] = $answers->{'post-renal_others'};
                            break;
                        case 33:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }

                    $data[] = $question;
                }
                break;
            case 4:
                $data = [];
                for ($i = 34; $i <= 47; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Risk::where('patient_id', $patient_id)
                        ->select(
                            'id', 'CKD_history', 'AK_history', 'cardiac-failure_history', 'LCF_history', 'neurological-impairment_disability_history',
                            'sepsis_history', 'contrast_media', 'drugs-with-potential-nephrotoxicity', 'drug_name', 'hypovolemia_history',
                            'malignancy_history', 'trauma_history', 'autoimmune-disease_history', 'other-risk-factors', 'other-risk-factors'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 34:
                            $question['answer'] = $answers->{'CKD_history'};
                            break;
                        case 35:
                            $question['answer'] = $answers->{'AK_history'};
                            break;
                        case 36:
                            $question['answer'] = $answers->{'cardiac-failure_history'};
                            break;
                        case 37:
                            $question['answer'] = $answers->{'LCF_history'};
                            break;
                        case 38:
                            $question['answer'] = $answers->{'neurological-impairment_disability_history'};
                            break;
                        case 39:
                            $question['answer'] = $answers->{'sepsis_history'};
                            break;
                        case 40:
                            $question['answer'] = $answers->{'contrast_media'};
                            break;
                        case 41:
                            $question['answer'] = $answers->{'drugs-with-potential-nephrotoxicity'};
                            break;
                        case 42:
                            $question['answer'] = $answers->{'drug_name'};
                            break;
                        case 43:
                            $question['answer'] = $answers->{'hypovolemia_history'};
                            break;
                        case 44:
                            $question['answer'] = $answers->{'malignancy_history'};
                            break;
                        case 45:
                            $question['answer'] = $answers->{'trauma_history'};
                            break;
                        case 46:
                            $question['answer'] = $answers->{'autoimmune-disease_history'};
                            break;
                        case 47:
                            $question['answer'] = $answers->{'other-risk-factors'};
                            break;

                    }

                    $data[] = $question;
                }
                break;
            case 5:
                $data = [];
                for ($i = 48; $i <= 70; $i++) {
                    if ($i === 58 || $i === 60 || $i === 64 || $i === 67 || $i === 69) {
                        continue;
                    }
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Assessment::where('patient_id', $patient_id)
                        ->select(
                            'id', 'heart-rate/minute', 'respiratory-rate/minute', 'SBP', 'DBP', 'GCS', 'oxygen_saturation', 'temperature', 'UOP', 'AVPU',
                            'skin_examination', 'skin_examination_clarify', 'eye_examination', 'eye_examination_clarify', 'ear_examination',
                            'ear_examination_clarify', 'cardiac_examination', 'cardiac_examination_clarify', 'internal_jugular_vein', 'chest_examination',
                            'chest_examination_clarify', 'abdominal_examination', 'abdominal_examination_clarify', 'other'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 48:
                            $question['answer'] = $answers->{'heart-rate/minute'};
                            break;
                        case 49:
                            $question['answer'] = $answers->{'respiratory-rate/minute'};
                            break;
                        case 50:
                            $question['answer'] = $answers->{'SBP'};
                            break;
                        case 51:
                            $question['answer'] = $answers->{'DBP'};
                            break;
                        case 52:
                            $question['answer'] = $answers->{'GCS'};
                            break;
                        case 53:
                            $question['answer'] = $answers->{'oxygen_saturation'};
                            break;
                        case 54:
                            $question['answer'] = $answers->{'temperature'};
                            break;
                        case 55:
                            $question['answer'] = $answers->{'UOP'};
                            break;
                        case 56:
                            $question['answer'] = $answers->{'AVPU'};
                            break;
                        case 57:
                            $question['answer'] = [
                                'answers' => $answers->{'skin_examination'},
                                'other_field' => $answers->{'skin_examination_clarify'},
                            ];
                            break;
                        case 59:
                            $question['answer'] = [
                                'answers' => $answers->{'eye_examination'},
                                'other_field' => $answers->{'eye_examination_clarify'},
                            ];
                            break;
                        case 61:
                            $question['answer'] = $answers->{'ear_examination'};
                            break;
                        case 62:
                            $question['answer'] = $answers->{'ear_examination_clarify'};
                            break;
                        case 63:
                            $question['answer'] = [
                                'answers' => $answers->{'cardiac_examination'},
                                'other_field' => $answers->{'cardiac_examination_clarify'},
                            ];
                            break;
                        case 65:
                            $question['answer'] = $answers->{'internal_jugular_vein'};
                            break;
                        case 66:
                            $question['answer'] = [
                                'answers' => $answers->{'chest_examination'},
                                'other_field' => $answers->{'chest_examination_clarify'},
                            ];
                            break;
                        case 68:
                            $question['answer'] = [
                                'answers' => $answers->{'abdominal_examination'},
                                'other_field' => $answers->{'abdominal_examination_clarify'},
                            ];
                            break;
                        case 70:
                            $question['answer'] = $answers->{'other'};
                            break;

                    }

                    $data[] = $question;
                }
                break;
            case 6:
                $data = [];
                for ($i = 71; $i <= 76; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Examination::where('patient_id', $patient_id)
                        ->select(
                            'id', 'current_creatinine', 'basal_creatinine', 'renal_US', 'specify_renal-US',
                            'Other laboratory findings', 'Other radiology findings'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 71:
                            $question['answer'] = $answers->{'current_creatinine'};
                            break;
                        case 72:
                            $question['answer'] = $answers->{'basal_creatinine'};
                            break;
                        case 73:
                            $question['answer'] = $answers->{'renal_US'};
                            break;
                        case 74:
                            $question['answer'] = $answers->{'specify_renal-US'};
                            break;
                        case 75:
                            $question['answer'] = $answers->{'Other laboratory findings'};
                            break;
                        case 76:
                            $question['answer'] = $answers->{'Other radiology findings'};
                            break;
                    }

                    $data[] = $question;
                }
                break;
            case 7:
                $data = [];
                for ($i = 77; $i <= 78; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Decision::where('patient_id', $patient_id)
                        ->select(
                            'id', 'medical_decision', 'other'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 77:
                            $question['answer'] = $answers->{'medical_decision'};
                            break;
                        case 78:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }

                    $data[] = $question;
                }
                break;
            case 8:
                $data = [];
                for ($i = 79; $i <= 82; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                        ->where('id', $i)
                        ->select('id', 'question', 'values', 'type', 'keyboard_type', 'mandatory', 'updated_at')
                        ->first();

                    $answers = Outcome::where('patient_id', $patient_id)
                        ->select(
                            'id', 'outcome_of_the_patient', 'creatinine_on_discharge', 'final_status', 'other'
                        )
                        ->first();

                    $question = [
                        'id' => $questions->{'id'},
                        'question' => $questions->{'question'},
                        'values' => $questions->{'values'},
                        'type' => $questions->{'type'},
                        'keyboard_type' => $questions->{'keyboard_type'},
                        'mandatory' => $questions->{'mandatory'},
                        'updated_at' => $questions->{'updated_at'},
                    ];

                    switch ($i) {
                        case 79:
                            $question['answer'] = $answers->{'outcome_of_the_patient'};
                            break;
                        case 80:
                            $question['answer'] = $answers->{'creatinine_on_discharge'};
                            break;
                        case 81:
                            $question['answer'] = $answers->{'final_status'};
                            break;
                        case 82:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }

                    $data[] = $question;
                }
                break;
        }
        $response = [
            'value' => true,
            'data' => $data,
        ];

        return response($response, 200);
    }

    public function ShowQuestitionsAnswarsbkp($section_id, $patient_id)
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
