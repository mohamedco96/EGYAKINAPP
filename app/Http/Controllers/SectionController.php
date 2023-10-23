<?php

namespace App\Http\Controllers;

use App\Models\Questions;
use App\Models\{Assessment, Cause, Complaint, Examination, PatientHistory, Risk, Section, User, Score, ScoreHistory,Decision,Outcome};
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class SectionController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $section = Section::all();

        if($section!=null){
            $response = [
                'value' => true,
                'data' => $section
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }

    }


    //@param \Illuminate\Http\Request $request
   // @return \Illuminate\Http\Response
    public function store(StorePatientHistoryRequest $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $submit_status = Section::where('patient_id', $patient_id)->get(['submit_status'])->first();

        $sections = Section::where('patient_id', $patient_id)
        ->select('section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6','section_7')
        ->first();
    
    $updated_at = [
        'updated_at1' => PatientHistory::where('id', $patient_id)->value('updated_at'),
        'updated_at2' => Complaint::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at3' => Cause::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at4' => Risk::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at5' => Assessment::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at6' => Examination::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at7' => Decision::where('patient_id', $patient_id)->value('updated_at'),
    ];
    
    $data = [];
    for ($i = 1; $i <= 7; $i++) {
        $section = [
            'section_id' => $i,
            'section_status' => $sections->{'section_'.$i},
            'updated_at' => $updated_at['updated_at'.$i],
        ];
    
        switch ($i) {
            case 1:
                $section['section_name'] = 'Patient History';
                break;
            case 2:
                $section['section_name'] = 'Complaint';
                break;
            case 3:
                $section['section_name'] = 'Cause of AKI';
                break;
            case 4:
                $section['section_name'] = 'Risk factors for AKI';
                break;
            case 5:
                $section['section_name'] = 'Assessment of the patient';
                break;
            case 6:
                $section['section_name'] = 'Laboratory and radiology results'; //Medical examinations
                break;
            case 7:
                $section['section_name'] = 'Medical decision'; //Medical examinations
                break;
        }
    
        $data[] = $section;
    }
    
    if ($sections) {
        $response = [
            'value' => true,
            // 'data' => $values
        ];
        return response()->json([
            'value' => true,
            'submit_status' => $submit_status->submit_status,
            'data' => $data,
        ]);
    } else {
        $response = [
            'value' => false,
        ];
        return response($response, 404);
    }
    }

    public function showSection($section_id,$patient_id)
    {

        $section = Section::where('patient_id', $patient_id)->get();
        $patient = PatientHistory::where('id', $patient_id)->get();
        $questions = Questions::where('section_id', $section_id)->get();

        $values = [
            'Questions' => $questions,
            'Section' => $section,
            'Patient' => $patient,
        ];

        if($section!=null){
            $response = [
                'value' => true,
                'data' => $values
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request,$section_id,$patient_id)
    {
        //$Patient = PatientHistory::find($patient_id);
        $questionMap =$request->all();
        switch ($section_id) {
            case 1:
                if ($request->has('1')) {
                    PatientHistory::where('id', $patient_id)->update(['name' => $request->input('1')]);
                }
                
                if ($request->has('2')) {
                    PatientHistory::where('id', $patient_id)->update(['hospital' => $request->input('2')]);
                }
    
                if ($request->has('3')) {
                    PatientHistory::where('id', $patient_id)->update(['collected_data_from' => $request->input('3')]);
                }
                
                if ($request->has('4')) {
                    PatientHistory::where('id', $patient_id)->update(['NID' => $request->input('4')]);
                }
                
                if ($request->has('5')) {
                    PatientHistory::where('id', $patient_id)->update(['phone' => $request->input('5')]);
                }
                
                if ($request->has('6')) {
                    PatientHistory::where('id', $patient_id)->update(['email' => $request->input('6')]);
                }
                
                if ($request->has('7')) {
                    PatientHistory::where('id', $patient_id)->update(['age' => $request->input('7')]);
                }
                
                if ($request->has('8')) {
                    PatientHistory::where('id', $patient_id)->update(['gender' => $request->input('8')]);
                }
                
                if ($request->has('9')) {
                    PatientHistory::where('id', $patient_id)->update(['occupation' => $request->input('9')]);
                }
                
                if ($request->has('10')) {
                    PatientHistory::where('id', $patient_id)->update(['residency' => $request->input('10')]);
                }
                
                if ($request->has('11')) {
                    PatientHistory::where('id', $patient_id)->update(['governorate' => $request->input('11')]);
                }
                
                if ($request->has('12')) {
                    PatientHistory::where('id', $patient_id)->update(['marital_status' => $request->input('12')]);
                }
                
                if ($request->has('13')) {
                    PatientHistory::where('id', $patient_id)->update(['educational_level' => $request->input('13')]);
                }
                
                if ($request->has('14')) {
                    PatientHistory::where('id', $patient_id)->update(['special_habits_of_the_patient' => $request->input('14')]);
                }
                
                if ($request->has('15')) {
                    PatientHistory::where('id', $patient_id)->update(['DM' => $request->input('15')]);
                }
                
                if ($request->has('16')) {
                    PatientHistory::where('id', $patient_id)->update(['DM_duration' => $request->input('16')]);
                }
                
                if ($request->has('17')) {
                    PatientHistory::where('id', $patient_id)->update(['HTN' => $request->input('17')]);
                }
                
                if ($request->has('18')) {
                    PatientHistory::where('id', $patient_id)->update(['HTN_duration' => $request->input('18')]);
                }
                
                if ($request->has('19')) {
                    PatientHistory::where('id', $patient_id)->update(['other' => $request->input('19')]);
                }
                $response = [
                    'value' => true,
                    'map' => $questionMap,
                    'message' => 'Patient Updated Successfully'
                ];
            break;
            case 2:
                if ($request->has('20')) {
                    Complaint::where('patient_id', $patient_id)->update(['where_was_th_patient_seen_for_the_first_time' => $request->input('20')]);
                }
                
                if ($request->has('21')) {
                    Complaint::where('patient_id', $patient_id)->update(['place_of_admission' => $request->input('21')]);
                }
    
                if ($request->has('22')) {
                    Complaint::where('patient_id', $patient_id)->update(['date_of_admission' => $request->input('22')]);
                }
                
                if ($request->has('23')) {
                    Complaint::where('patient_id', $patient_id)->update(['main_omplaint' => $request->input('23')]);
                }
                
                if ($request->has('24')) {
                    Complaint::where('patient_id', $patient_id)->update(['other' => $request->input('24')]);
                }

                $response = [
                    'value' => true,
                    'map' => $questionMap,
                    'message' => 'Complaint Updated Successfully'
                ];
            break;
        } 

        return response()->json($response, 201);
    }

        /**
     * Update the specified resource in storage.
     */
    public function updateFinalSubmit(UpdatePatientHistoryRequest $request, $patient_id)
    {
        $patient = Section::where('patient_id', $patient_id)->first();

        if($patient!=null){

            DB::table('sections')->where('patient_id', $patient_id)->update(['submit_status' => true]);

            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();
            
            $incrementAmount = 10; // Example increment amount
            $action = 'Final Submit'; // Example action
            
            if ($score) {
                $score->increment('score', $incrementAmount); // Increase the score
            } else {
                Score::create([
                    'doctor_id' => $doctorId,
                    'score' => $incrementAmount,
                ]);
            }
    
            ScoreHistory::create([
                'doctor_id' => $doctorId,
                'score' => $incrementAmount,
                'action' => $action,
                'timestamp' => now(),
            ]);


            $response = [
                'value' => true,
                'data' => $Examination,
                'message' => 'Examination Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Examination was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $section = Section::where('patient_id', $id)->first();

        if($section!=null){
            DB::table('sections')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'section Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No section was found'
            ];
            return response($response, 404);
        }
    }
}
