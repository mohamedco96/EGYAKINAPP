<?php

namespace App\Http\Controllers;

use App\Models\Questions;
use App\Models\PatientHistory;
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
    public function show($id)
    {
        $Questions = Questions::where('section_id', $id)->get();
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

    public function ShowQuestitionsAnswars($id,$patient_id)
    {
        $data = [];
        for ($i = 1; $i <= 7; $i++) {
            $questions = Questions::where('section_id', $id)
            ->where('id', $i)
            ->select('id', 'question', 'values', 'type', 'mandatory', 'updated_at')
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
            }
            
            $data[] = $question;
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
