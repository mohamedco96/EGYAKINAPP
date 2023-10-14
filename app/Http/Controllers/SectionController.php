<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Examination;
use App\Models\PatientHistory;
use App\Models\Questions;
use App\Models\Risk;
use App\Models\Section;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use Illuminate\Support\Facades\DB;

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
        ->select('section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6')
        ->first();
    
    $updated_at = [
        'updated_at1' => PatientHistory::where('id', $patient_id)->value('updated_at'),
        'updated_at2' => Complaint::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at3' => Cause::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at4' => Risk::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at5' => Assessment::where('patient_id', $patient_id)->value('updated_at'),
        'updated_at6' => Examination::where('patient_id', $patient_id)->value('updated_at'),
    ];
    
    $data = [];
    for ($i = 1; $i <= 6; $i++) {
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
    public function update(UpdatePatientHistoryRequest $request, $id)
    {

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
