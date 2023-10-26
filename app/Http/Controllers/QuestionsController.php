<?php

namespace App\Http\Controllers;

use App\Models\Questions;
use App\Models\{Assessment, Cause, Complaint, Examination, PatientHistory, Risk, Section, User, Score, ScoreHistory,Decision,Outcome};
use App\Http\Requests\StoreQuestionsRequest;
use App\Http\Requests\UpdateQuestionsRequest;
use Illuminate\Support\Facades\DB;

class QuestionsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Questions = Questions::latest()->paginate(10);
        $Questions = Questions::get();

        if($Questions!=null){
            $response = [
                'value' => true,
                'data' => $Questions
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreQuestionsRequest $request)
    {
        $Questions = Questions::create($request->all());

        if($Questions!=null){
            $response = [
                'value' => true,
                'data' => $Questions
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($section_id)
    {
        $data = [];
        for ($i = 1; $i <= 19; $i++) {
            $questions = Questions::where('section_id', $section_id)
            ->where('id', $i)
            ->select('id', 'question', 'values', 'type', 'keyboard_type','mandatory', 'updated_at')
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
         
            $data[] = $question;
        }
        $response = [
            'value' => true,
            'data' => $data,
        ];
        return response($response, 200);
    }

    public function ShowQuestitionsAnswars($section_id,$patient_id)
    {
        switch ($section_id) {
            case 1:
                $data = [];
                for ($i = 1; $i <= 19; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                    ->where('id', $i)
                    ->select('id', 'question', 'values', 'type', 'keyboard_type','mandatory', 'updated_at')
                    ->first();
        
                    $answers = PatientHistory::where('id', $patient_id)
                    ->select('id','name','hospital','collected_data_from','NID','phone','email','age','gender','occupation',
                    'residency','governorate','marital_status','educational_level','special_habits_of_the_patient','DM',
                    'DM_duration','HTN','HTN_duration','other',)
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
                            $question['answer'] = $answers->{'special_habits_of_the_patient'};
                            break;
                        case 15:
                            $question['answer'] = $answers->{'DM'};
                            break;
                        case 16:
                            $question['answer'] = $answers->{'DM_duration'};
                            break;
                        case 17:
                            $question['answer'] = $answers->{'HTN'};
                            break;
                        case 18:
                            $question['answer'] = $answers->{'HTN_duration'}; 
                            break;
                        case 19:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }
                    
                    $data[] = $question;
                }
                break;
            case 2:
                $data = [];
                for ($i = 20; $i <= 24; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                    ->where('id', $i)
                    ->select('id', 'question', 'values', 'type', 'keyboard_type','mandatory', 'updated_at')
                    ->first();
        
                    $answers = Complaint::where('patient_id', $patient_id)
                    ->select('id','where_was_th_patient_seen_for_the_first_time','place_of_admission',
                    'date_of_admission','main_omplaint','other')
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
                        case 20:
                            $question['answer'] = $answers->{'where_was_th_patient_seen_for_the_first_time'};
                            break;
                        case 21:
                            $question['answer'] = $answers->{'place_of_admission'};
                            break;
                        case 22:
                            $question['answer'] = $answers->{'date_of_admission'};
                            break;
                        case 23:
                            $question['answer'] = $answers->{'main_omplaint'};
                            break;
                        case 24:
                            $question['answer'] = $answers->{'other'};
                            break;
                    }
                    
                    $data[] = $question;
                }
                break;
            case 3:
                $data = [];
                for ($i = 25; $i <= 32; $i++) {
                    $questions = Questions::where('section_id', $section_id)
                    ->where('id', $i)
                    ->select('id', 'question', 'values', 'type', 'keyboard_type','mandatory', 'updated_at')
                    ->first();
        
                    $answers = Cause::where('patient_id', $patient_id)
                    ->select('id',
                    'cause_of_AKI','pre-renal_causes','pre-renal_others','renal_causes','renal_others','post-renal_causes','post-renal_others','other'
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
                        case 25:
                            $question['answer'] = $answers->{'cause_of_AKI'};
                            break;
                        case 26:
                            $question['answer'] = $answers->{'pre-renal_causes'};
                            break;
                        case 27:
                            $question['answer'] = $answers->{'pre-renal_others'};
                            break;
                        case 28:
                            $question['answer'] = $answers->{'renal_causes'};
                            break;
                        case 29:
                            $question['answer'] = $answers->{'renal_others'};
                            break;
                        case 30:
                            $question['answer'] = $answers->{'post-renal_causes'};
                            break;
                        case 31:
                            $question['answer'] = $answers->{'post-renal_others'};
                            break;
                        case 32:
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
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionsRequest $request, $id)
    {
        $Questions = Questions::find($id)->first();

        if($Questions!=null){
            $Questions->update($request->all());
            $response = [
                'value' => true,
                'data' => $Questions,
                'message' => 'Questions Updated Successfully'
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Questions = Questions::find($id)->first();

        if($Questions!=null){
            DB::table('questions')->where('section_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Questions Deleted Successfully'
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Questions was found'
            ];
            return response($response, 404);
        }
    }
}
