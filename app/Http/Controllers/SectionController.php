<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSectionRequest;
use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\PatientHistory;
use App\Models\Questions;
use App\Models\Risk;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $section = Section::all();

        if ($section != null) {
            $response = [
                'value' => true,
                'data' => $section,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
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
            ->select('section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6', 'section_7')
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

    public function showSection($section_id, $patient_id)
    {

        $section = Section::where('patient_id', $patient_id)->get();
        $patient = PatientHistory::where('id', $patient_id)->get();
        $questions = Questions::where('section_id', $section_id)->get();

        $values = [
            'Questions' => $questions,
            'Section' => $section,
            'Patient' => $patient,
        ];

        if ($section != null) {
            $response = [
                'value' => true,
                'data' => $values,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, $section_id, $patient_id)
    {
        switch ($section_id) {
            case 1:
                $fields = [
                    '1' => 'name',
                    '2' => 'hospital',
                    '3' => 'collected_data_from',
                    '4' => 'NID',
                    '5' => 'phone',
                    '6' => 'email',
                    '7' => 'age',
                    '8' => 'gender',
                    '9' => 'occupation',
                    '10' => 'residency',
                    '11' => 'governorate',
                    '12' => 'marital_status',
                    '13' => 'educational_level',
                    '14.answers' => 'special_habits_of_the_patient',
                    '14.other_field' => 'other_habits_of_the_patient',
                    '16' => 'DM',
                    '17' => 'DM_duration',
                    '18' => 'HTN',
                    '19' => 'HTN_duration',
                    '20' => 'other',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        PatientHistory::where('id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }
                $response = [
                    'value' => true,
                    'message' => 'PatientHistory Updated Successfully',
                ];
                $action = 'Update PatientHistory Section';

                break;

            case 2:
                $fields = [
                    '21' => 'where_was_th_patient_seen_for_the_first_time',
                    '22' => 'place_of_admission',
                    '23' => 'date_of_admission',
                    '24.answers' => 'main_omplaint',
                    '24.other_field' => 'other',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Complaint::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_2' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Complaint Updated Successfully',
                ];
                $action = 'Update Complaint Section';

                break;
            case 3:
                $fields = [
                    '26' => 'cause_of_AKI',
                    '27.answers' => 'pre-renal_causes',
                    '27.other_field' => 'pre-renal_others',
                    '29.answers' => 'renal_causes',
                    '29.other_field' => 'renal_others',
                    '31' => 'post-renal_causes',
                    '32' => 'post-renal_others',
                    '33' => 'other',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Cause::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_3' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Cause Updated Successfully',
                ];
                $action = 'Update Cause of AKI Section';
                break;

            case 4:
                $fields = [
                    '34' => 'CKD_history',
                    '35' => 'AK_history',
                    '36' => 'cardiac-failure_history',
                    '37' => 'LCF_history',
                    '38' => 'neurological-impairment_disability_history',
                    '39' => 'sepsis_history',
                    '40' => 'contrast_media',
                    '41' => 'drugs-with-potential-nephrotoxicity',
                    '42' => 'drug_name',
                    '43' => 'hypovolemia_history',
                    '44' => 'malignancy_history',
                    '45' => 'trauma_history',
                    '46' => 'autoimmune-disease_history',
                    '47' => 'other-risk-factors',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Risk::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_4' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Risk Updated Successfully',
                ];
                $action = 'Update Risk Section';
                break;
            case 5:
                $fields = [
                    '48' => 'heart-rate/minute',
                    '49' => 'respiratory-rate/minute',
                    '50' => 'SBP',
                    '51' => 'DBP',
                    '52' => 'GCS',
                    '53' => 'oxygen_saturation',
                    '54' => 'temperature',
                    '55' => 'UOP',
                    '56' => 'AVPU',
                    '57' => 'skin_examination',
                    '58' => 'skin_examination_clarify',
                    '59' => 'eye_examination',
                    '60' => 'eye_examination_clarify',
                    '61' => 'ear_examination',
                    '62' => 'ear_examination_clarify',
                    '63' => 'cardiac_examination',
                    '64' => 'cardiac_examination_clarify',
                    '65' => 'internal_jugular_vein',
                    '66' => 'chest_examination',
                    '67' => 'chest_examination_clarify',
                    '68' => 'abdominal_examination',
                    '69' => 'abdominal_examination_clarify',
                    '70' => 'other',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Assessment::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_5' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Assessment Updated Successfully',
                ];
                $action = 'Update Assessment Section';
                break;
            case 6:
                $fields = [
                    '71' => 'current_creatinine',
                    '72' => 'basal_creatinine',
                    '73' => 'renal_US',
                    '74' => 'specify_renal-US',
                    '75' => 'Other laboratory findings',
                    '76' => 'Other radiology findings',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Examination::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_6' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Examination Updated Successfully',
                ];
                $action = 'Update Examination Section';
                break;
            case 7:
                $fields = [
                    '77' => 'medical_decision',
                    '78' => 'other',
                ];

                foreach ($fields as $field => $column) {
                    if ($request->has($field)) {
                        Decision::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                    }
                }

                DB::table('sections')->where('patient_id', $patient_id)->update(['section_7' => true]);

                $response = [
                    'value' => true,
                    'message' => 'Decision Updated Successfully',
                ];
                $action = 'Update Decision Section';
                break;
        }

        // Scoring system
        $doctorId = auth()->user()->id;
        $incrementAmount = 5;
        //$action = 'Update '.($section_id === 1 ? 'Patient' : 'Complaint').' Section';

        $score = Score::where('doctor_id', $doctorId)->first();
        if ($score) {
            $score->increment('score', $incrementAmount);
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

        return $this->successResponse($response, 201);
    }

    private function successResponse($data, $statusCode)
    {
        $response = [
            'value' => true,
            'data' => $data,
            'message' => $data['message'],
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateFinalSubmit(UpdateSectionRequest $request, $patient_id)
    {
        $patient = Section::where('patient_id', $patient_id)->first();

        if ($patient != null) {

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
                'message' => 'Final Submit Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'Final Submit was found',
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

        if ($section != null) {
            DB::table('sections')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'section Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No section was found',
            ];

            return response($response, 404);
        }
    }
}
