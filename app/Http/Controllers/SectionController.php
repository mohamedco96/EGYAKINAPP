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
                $section['section_name'] = 'Medical decision';
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
                    PatientHistory::where('id', $patient_id)->update(['special_habits_of_the_patient' => $request->input('14.answer')]);
                    PatientHistory::where('id', $patient_id)->update(['other_habits_of_the_patient' => $request->input('14.other_field')]);
                }
                
                if ($request->has('16')) {
                    PatientHistory::where('id', $patient_id)->update(['DM' => $request->input('16')]);
                }
                
                if ($request->has('17')) {
                    PatientHistory::where('id', $patient_id)->update(['DM_duration' => $request->input('17')]);
                }
                
                if ($request->has('18')) {
                    PatientHistory::where('id', $patient_id)->update(['HTN' => $request->input('18')]);
                }
                
                if ($request->has('19')) {
                    PatientHistory::where('id', $patient_id)->update(['HTN_duration' => $request->input('19')]);
                }
                
                if ($request->has('20')) {
                    PatientHistory::where('id', $patient_id)->update(['other' => $request->input('20')]);
                }
                $response = [
                    'value' => true,
                    'map' => $questionMap,
                    'message' => 'Patient Updated Successfully'
                ];
            break;
            case 2:
                if ($request->has('21')) {
                    Complaint::where('patient_id', $patient_id)->update(['where_was_th_patient_seen_for_the_first_time' => $request->input('21')]);
                }
                
                if ($request->has('22')) {
                    Complaint::where('patient_id', $patient_id)->update(['place_of_admission' => $request->input('22')]);
                }
    
                if ($request->has('23')) {
                    Complaint::where('patient_id', $patient_id)->update(['date_of_admission' => $request->input('23')]);
                }
                
                if ($request->has('24')) {
                    Complaint::where('patient_id', $patient_id)->update(['main_omplaint' => $request->input('24.main_omplaint')]);
                    Complaint::where('patient_id', $patient_id)->update(['other' => $request->input('24.other')]);
                }


                DB::table('sections')->where('patient_id', $patient_id)->update(['section_2' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Complaint Section'; // Example action
                
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
                    'map' => $questionMap,
                    'message' => 'Complaint Updated Successfully'
                ];
            break;
            case 3:
                if ($request->has('26')) {
                    Cause::where('patient_id', $patient_id)->update(['cause_of_AKI' => $request->input('26')]);
                }
                
                if ($request->has('27')) {
                    Cause::where('patient_id', $patient_id)->update(['pre-renal_causes' => $request->input('27.pre-renal_causes')]);
                    Cause::where('patient_id', $patient_id)->update(['pre-renal_others' => $request->input('27.pre-renal_others')]);
                }
                
                if ($request->has('29')) {
                    Cause::where('patient_id', $patient_id)->update(['renal_causes' => $request->input('29.renal_causes')]);
                    Cause::where('patient_id', $patient_id)->update(['renal_others' => $request->input('29.renal_others')]);
                }


                if ($request->has('31')) {
                    Cause::where('patient_id', $patient_id)->update(['post-renal_causes' => $request->input('31.post-renal_causes')]);
                    Cause::where('patient_id', $patient_id)->update(['post-renal_others' => $request->input('31.post-renal_others')]);
                }

                if ($request->has('33')) {
                    Cause::where('patient_id', $patient_id)->update(['other' => $request->input('33')]);
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_3' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Cause of AKI Section'; // Example action
                
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
                    'map' => $questionMap,
                    'message' => 'Cause Updated Successfully'
                ];
            break;
            case 4:
                if ($request->has('34')) {
                    Risk::where('patient_id', $patient_id)->update(['CKD_history' => $request->input('34')]);
                }
                if ($request->has('35')) {
                    Risk::where('patient_id', $patient_id)->update(['AK_history' => $request->input('35')]);
                }
                if ($request->has('36')) {
                    Risk::where('patient_id', $patient_id)->update(['cardiac-failure_history' => $request->input('36')]);
                }
                if ($request->has('37')) {
                    Risk::where('patient_id', $patient_id)->update(['LCF_history' => $request->input('37')]);
                }
                if ($request->has('38')) {
                    Risk::where('patient_id', $patient_id)->update(['neurological-impairment_disability_history' => $request->input('38')]);
                }
                if ($request->has('39')) {
                    Risk::where('patient_id', $patient_id)->update(['sepsis_history' => $request->input('39')]);
                }
                if ($request->has('40')) {
                    Risk::where('patient_id', $patient_id)->update(['contrast_media' => $request->input('40')]);
                }
                if ($request->has('41')) {
                    Risk::where('patient_id', $patient_id)->update(['drugs-with-potential-nephrotoxicity' => $request->input('41')]);
                }
                if ($request->has('42')) {
                    Risk::where('patient_id', $patient_id)->update(['drug_name' => $request->input('42')]);
                }
                if ($request->has('43')) {
                    Risk::where('patient_id', $patient_id)->update(['hypovolemia_history' => $request->input('43')]);
                }
                if ($request->has('44')) {
                    Risk::where('patient_id', $patient_id)->update(['malignancy_history' => $request->input('44')]);
                }
                if ($request->has('45')) {
                    Risk::where('patient_id', $patient_id)->update(['trauma_history' => $request->input('45')]);
                }
                if ($request->has('46')) {
                    Risk::where('patient_id', $patient_id)->update(['autoimmune-disease_history' => $request->input('46')]);
                }
                if ($request->has('47')) {
                    Risk::where('patient_id', $patient_id)->update(['other-risk-factors' => $request->input('47')]);
                }
                
                DB::table('sections')->where('patient_id', $patient_id)->update(['section_4' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Risk Section'; // Example action
                
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
                    'message' => 'Risk Updated Successfully'
                ];
            break;
            case 5:
                if ($request->has('48')) {
                    Assessment::where('patient_id', $patient_id)->update(['heart-rate/minute' => $request->input('48')]);
                }
                if ($request->has('49')) {
                    Assessment::where('patient_id', $patient_id)->update(['respiratory-rate/minute' => $request->input('49')]);
                }
                if ($request->has('50')) {
                    Assessment::where('patient_id', $patient_id)->update(['SBP' => $request->input('50')]);
                }
                if ($request->has('51')) {
                    Assessment::where('patient_id', $patient_id)->update(['DBP' => $request->input('51')]);
                }
                if ($request->has('52')) {
                    Assessment::where('patient_id', $patient_id)->update(['GCS' => $request->input('52')]);
                }
                if ($request->has('53')) {
                    Assessment::where('patient_id', $patient_id)->update(['oxygen_saturation' => $request->input('53')]);
                }
                if ($request->has('54')) {
                    Assessment::where('patient_id', $patient_id)->update(['temperature' => $request->input('54')]);
                }
                if ($request->has('55')) {
                    Assessment::where('patient_id', $patient_id)->update(['UOP' => $request->input('55')]);
                }
                if ($request->has('56')) {
                    Assessment::where('patient_id', $patient_id)->update(['AVPU' => $request->input('56')]);
                }
                if ($request->has('57')) {
                    Assessment::where('patient_id', $patient_id)->update(['skin_examination' => $request->input('57.skin_examination')]);
                    Assessment::where('patient_id', $patient_id)->update(['skin_examination_clarify' => $request->input('57.skin_examination_clarify')]);
                }
                if ($request->has('59')) {
                    Assessment::where('patient_id', $patient_id)->update(['eye_examination' => $request->input('59.eye_examination')]);
                    Assessment::where('patient_id', $patient_id)->update(['eye_examination_clarify' => $request->input('59.eye_examination_clarify')]);
                }
                if ($request->has('61')) {
                    Assessment::where('patient_id', $patient_id)->update(['ear_examination' => $request->input('61')]);
                }
                if ($request->has('62')) {
                    Assessment::where('patient_id', $patient_id)->update(['ear_examination_clarify' => $request->input('62')]);
                }
                if ($request->has('63')) {
                    Assessment::where('patient_id', $patient_id)->update(['cardiac_examination' => $request->input('63.cardiac_examination')]);
                    Assessment::where('patient_id', $patient_id)->update(['cardiac_examination_clarify' => $request->input('63.cardiac_examination_clarify')]);
                }
                if ($request->has('65')) {
                    Assessment::where('patient_id', $patient_id)->update(['internal_jugular_vein' => $request->input('65')]);
                }
                if ($request->has('66')) {
                    Assessment::where('patient_id', $patient_id)->update(['chest_examination' => $request->input('66.chest_examination')]);
                    Assessment::where('patient_id', $patient_id)->update(['chest_examination_clarify' => $request->input('66.chest_examination_clarify')]);
                }
                if ($request->has('68')) {
                    Assessment::where('patient_id', $patient_id)->update(['abdominal_examination' => $request->input('68.abdominal_examination')]);
                    Assessment::where('patient_id', $patient_id)->update(['abdominal_examination_clarify' => $request->input('68.abdominal_examination_clarify')]);
                }
                if ($request->has('70')) {
                    Assessment::where('patient_id', $patient_id)->update(['other' => $request->input('70')]);
                }
                               
                DB::table('sections')->where('patient_id', $patient_id)->update(['section_5' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Assessment Section'; // Example action
                
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
                    'message' => 'Assessment Updated Successfully'
                ];
            break;
            case 6:
                if ($request->has('71')) {
                    Examination::where('patient_id', $patient_id)->update(['current_creatinine' => $request->input('71')]);
                }
                
                if ($request->has('72')) {
                    Examination::where('patient_id', $patient_id)->update(['basal_creatinine' => $request->input('72')]);
                }
    
                if ($request->has('73')) {
                    Examination::where('patient_id', $patient_id)->update(['renal_US' => $request->input('73')]);
                }
                
                if ($request->has('74')) {
                    Examination::where('patient_id', $patient_id)->update(['specify_renal-US' => $request->input('74')]);
                }
                
                DB::table('sections')->where('patient_id', $patient_id)->update(['section_6' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Examination Section'; // Example action
                
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
                    'map' => $questionMap,
                    'message' => 'Examination Updated Successfully'
                ];
            break;
            case 7:
                if ($request->has('75')) {
                    Decision::where('patient_id', $patient_id)->update(['medical_decision' => $request->input('75')]);
                }
 
                DB::table('sections')->where('patient_id', $patient_id)->update(['section_7' => true]);

                //scoring system
                $doctorId = auth()->user()->id; // Assuming you have authentication in place
                $score = Score::where('doctor_id', $doctorId)->first();
                
                $incrementAmount = 5; // Example increment amount
                $action = 'Update Decision Section'; // Example action
                
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
                    'map' => $questionMap,
                    'message' => 'Decision Updated Successfully'
                ];
            break;
        } 

        return response()->json($response, 201);
    }

        /**
     * Update the specified resource in storage.
     */
    public function updateFinalSubmit(UpdateSectionRequest $request, $patient_id)
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
                'message' => 'Final Submit Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'Final Submit was found'
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
