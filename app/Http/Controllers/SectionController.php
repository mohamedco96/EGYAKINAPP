<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSectionRequest;
use App\Models\Assessment;
use App\Models\Cause;
use App\Models\Complaint;
use App\Models\Decision;
use App\Models\Examination;
use App\Models\Outcome;
use App\Models\PatientHistory;
use App\Models\Questions;
use App\Models\Risk;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\Section;
use App\Notifications\ReachingSpecificPoints;
use Illuminate\Support\Facades\DB;
use App\Models\SectionFieldMapping;
use Illuminate\Support\Facades\Log;
use App\Models\SectionsInfo;
use Illuminate\Support\Facades\Auth;


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
        $submit_status = Section::where('patient_id', $patient_id)->value('submit_status');

        $sections = Section::where('patient_id', $patient_id)
            ->select('section_1', 'section_2', 'section_3', 'section_4', 'section_5', 'section_6', 'section_7')
            ->first();

        if (!$sections) {
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }
        $updated_at = [
            'updated_at1' => PatientHistory::where('id', $patient_id)->value('updated_at'),
            'updated_at2' => Complaint::where('patient_id', $patient_id)->value('updated_at'),
            'updated_at3' => Cause::where('patient_id', $patient_id)->value('updated_at'),
            'updated_at4' => Risk::where('patient_id', $patient_id)->value('updated_at'),
            'updated_at5' => Assessment::where('patient_id', $patient_id)->value('updated_at'),
            'updated_at6' => Examination::where('patient_id', $patient_id)->value('updated_at'),
            'updated_at7' => Decision::where('patient_id', $patient_id)->value('updated_at'),
        ];

        $sectionInfos = SectionsInfo::all();

        $data = [];
        foreach ($sectionInfos as $sectionInfo) {
            $section_id = $sectionInfo->id;
            $section_name = $sectionInfo->section_name;

            // Check if the key exists in the array before accessing it
            $updated_at_key = 'updated_at'.$section_id;
            $updated_at_value = isset($updated_at[$updated_at_key]) ? $updated_at[$updated_at_key] : null;

            $section = [
                'section_id' => $section_id,
                'section_status' => $sections->{'section_'.$section_id},
                'updated_at' => $updated_at_value,
                'section_name' => $section_name,
            ];

            $data[] = $section;
        }

        if ($sections) {
            return response()->json([
                'value' => true,
                'submit_status' => $submit_status,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'value' => false,
            ], 404);
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






    public function update(UpdateSectionRequest $request, $section_id, $patient_id)
    {
        try {
            // Define the mapping of section IDs to model names
            $modelMappings = [
                1 => PatientHistory::class,
                2 => Complaint::class,
                3 => Cause::class,
                4 => Risk::class,
                5 => Assessment::class,
                6 => Examination::class,
                7 => Decision::class,
                8 => Outcome::class,
                // Add more mappings as needed for other section IDs
            ];

            // Check if the section ID has a corresponding model mapping
            if (!isset($modelMappings[$section_id])) {
                Log::error("No model mapping found for section ID {$section_id}.");
                return response()->json([
                    'value' => false,
                    'message' => 'No model mapping found for the specified section ID.',
                ], 404);
            }

            // Retrieve field mappings for the specified section
            $fieldMappings = SectionFieldMapping::where('section_id', $section_id)->pluck('column_name', 'field_name');

            // If no field mappings found, return an error response
            if ($fieldMappings->isEmpty()) {
                Log::error("No field mappings found for section ID {$section_id}.");
                return response()->json([
                    'value' => false,
                    'message' => 'No field mappings found for the specified section.',
                ], 404);
            }

            // Get the model name based on the section ID
            $modelName = $modelMappings[$section_id];

            // Update the model based on field mappings
            foreach ($fieldMappings as $field => $column) {
                if ($request->has($field)) {
                    $modelName::where('patient_id', $patient_id)->update([$column => $request->input($field)]);
                }
            }

            // Response with success message and any additional data
            $response = [
                'value' => true,
                'message' => 'Section updated successfully.',
            ];

            Log::info("Section updated successfully for section ID {$section_id} and patient ID {$patient_id}.");
            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log any unexpected errors
            Log::error('Error updating section: ' . $e->getMessage());

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'An unexpected error occurred while updating the section. Please try again later.',
            ], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function updatebkp(UpdateSectionRequest $request, $section_id, $patient_id)
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
                    '57.answers' => 'skin_examination',
                    '57.other_field' => 'skin_examination_clarify',
                    '59.answers' => 'eye_examination',
                    '59.other_field' => 'eye_examination_clarify',
                    '61' => 'ear_examination',
                    '62' => 'ear_examination_clarify',
                    '63.answers' => 'cardiac_examination',
                    '63.other_field' => 'cardiac_examination_clarify',
                    '65' => 'internal_jugular_vein',
                    '66.answers' => 'chest_examination',
                    '66.other_field' => 'chest_examination_clarify',
                    '68.answers' => 'abdominal_examination',
                    '68.other_field' => 'abdominal_examination_clarify',
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
        /*
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
        ]);*/

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

        if (!$patient) {
            $response = [
                'value' => false,
                'message' => 'Patient not found',
            ];

            return response()->json($response, 404);
        }

        // Update submit status
        $patient->update(['submit_status' => true,'final_submited_at' => now()]);

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
