<?php

namespace App\Http\Controllers;

use App\Models\Patients;
use App\Http\Requests\UpdatePatientsRequest;
use App\Models\PatientStatus;
use App\Models\Questions;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\SectionsInfo;
use App\Models\Answers;
use App\Notifications\ReachingSpecificPoints;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDF;

class SectionsController extends Controller
{
    protected $Patients;

    public function __construct(Patients $Patients)
    {
        $this->Patients = $Patients;
    }

    /**
     * Calculate GFR for CKD.
     *
     * @param bool|null $isMale
     * @param int|null $age
     * @param float|null $creatinine
     * @return float
     */
    public function calculateGFRForCKD($gender, $age, $creatinine)
    {
        if (is_null($gender) || $age === 0 || $creatinine === 0) {
            return 0;
        }

        if ($gender === 'Male') {
            $A = 0.9;
            $B = ($creatinine <= 0.9) ? -0.302 : -1.200;
            $gfr = 142 * pow($creatinine / $A, $B) * pow(0.9938, $age);
        } else {
            $A = 0.7;
            $B = ($creatinine <= 0.7) ? -0.241 : -1.200;
            $gfr = 142 * pow($creatinine / $A, $B) * pow(0.9938, $age) * 1.012;
        }

        return number_format($gfr, 2, '.', '');
    }
    /**
     * Calculate Sobh Ccr.
     *
     * @param int|null $age
     * @param float|null $weight
     * @param float|null $height
     * @param float|null $serumCreatinine
     * @return float
     */
    public function calculateSobhCcr($age, $weight, $height, $serumCreatinine)
    {
        if (is_null($age) || is_null($weight) || is_null($height) || is_null($serumCreatinine)) {
            return 0;
        }

        $ccr = ((140 - $age) / $serumCreatinine) *
            pow($weight, 0.54) *
            pow($height, 0.40) *
            0.014;

        return number_format($ccr, 2, '.', '');
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateFinalSubmit(UpdatePatientsRequest $request, $patient_id)
    {
        $patientSubmitStatus = PatientStatus::where('patient_id', $patient_id)
            ->where('key', 'submit_status')->first();

        if (!$patientSubmitStatus) {
            $response = [
                'value' => false,
                'message' => 'Patient not found',
            ];

            return response()->json($response, 404);
        }

        // Update submit status
        $patientSubmitStatus->update(['status' => true]);

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
     * Show questions and answers for a specific section and patient.
     */

    public function showQuestionsAnswers($section_id, $patient_id)
    {
        try {
            // Check if the section exists
            $sectionExists = Questions::where('section_id', $section_id)->exists();
            if (!$sectionExists) {
                return response()->json([
                    'value' => false,
                    'message' => "Section not found",
                ], 404);
            }

            // Fetch questions dynamically based on section_id
            $questions = Questions::where('section_id', $section_id)
                ->orderBy('id')
                ->get();

            // Fetch all answers for the patient in one query
            $answers = Answers::where('patient_id', $patient_id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();

            // Initialize data array to store questions and answers
            $data = [];

            foreach ($questions as $question) {
                // Skip questions with certain IDs
                if ($question->skip) {
                    Log::info("Question with ID {$question->id} skipped as per skip flag.");
                    continue;
                }

                $questionData = [
                    'id' => $question->id,
                    'question' => $question->question,
                    'values' => $question->values,
                    'type' => $question->type,
                    'keyboard_type' => $question->keyboard_type,
                    'mandatory' => $question->mandatory,
                    'updated_at' => $question->updated_at,
                ];

                // Find the answer for this question from the fetched answers
                $answer = $answers->where('question_id', $question->id)->first();

                if ($question->type === 'multiple') {
                    // Initialize the answer array
                    $questionData['answer'] = [
                        'answers' => [], // Initialize answers as an empty array
                        'other_field' => null // Set other_field to null by default
                    ];

                    // Find answers for this question from the fetched answers
                    $questionAnswers = $answers->where('question_id', $question->id);

                    // Populate the answers array
                    foreach ($questionAnswers as $answer) {
                        if ($answer->type !== 'other') {
                            $questionData['answer']['answers'] = $answer->answer;
                        }
                        if ($answer->type === 'other') {
                            $questionData['answer']['other_field'] = $answer->answer;
                        }
                    }
                } else {
                    // For other types of questions, return the answer directly
                    $questionData['answer'] = $answer ? $answer->answer : null;
                }

                $data[] = $questionData;
            }

            $submitter = PatientStatus::select('id', 'doctor_id')
                ->where('patient_id', $patient_id)
                ->where('key', 'outcome_status')
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
                }])
                ->first(); // Use first() instead of get() to get a single record

            if ($section_id == 8) {
                if ($submitter && $submitter->doctor) {
                    $doctor = $submitter->doctor;
                    $response = [
                        'value' => true,
                        'Submitter' => [
                            'name' => $doctor->name . ' ' . $doctor->lname,
                            'image' => $doctor->image,
                        ],
                        'data' => $data
                    ];
                } else {
                    $response = [
                        'value' => true,
                        'Submitter' => [
                            'name' => null,
                            'image' => null,
                        ],
                        'data' => $data
                    ];
                }
            } else {
                $response = [
                    'value' => true,
                    'data' => $data,
                ];
            }

            Log::info("Questions and answers retrieved successfully for section ID {$section_id} and patient ID {$patient_id}.");

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error("Error while fetching questions and answers: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function showSections($patient_id)
    {
        // Fetch patient submit status
        $submit_status = PatientStatus::where('patient_id', $patient_id)
            ->where('key', 'submit_status')
            ->value('status');

        // Fetch sections data
        $sections = PatientStatus::select('key', 'status', 'updated_at')
            ->where('patient_id', $patient_id)
            ->where('key', 'LIKE', 'section_%')
            ->get();

        // Fetch patient name and doctor ID
        $patient_name = Answers::where('patient_id', $patient_id)
            ->where('question_id', '1')
            ->value('answer');

        $doctor_Id = Patients::where('id', $patient_id)->value('doctor_id');

        // Handle case where patient name or sections are not found
        if (!$patient_name) {
            Log::error("Patient name not found for patient ID: $patient_id");
            return response()->json([
                'value' => false,
                'message' => 'Patient not found for the given patient ID.',
            ], 404);
        }

        if ($sections->isEmpty()) {
            Log::warning("Sections not found for patient ID: $patient_id");
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }

        // Fetch section information from SectionsInfo model
        $sectionInfos = SectionsInfo::where('id', '<>', 8)->get();

        // Initialize data array for storing section information
        $data = [];
        foreach ($sectionInfos as $sectionInfo) {
            $section_id = $sectionInfo->id;
            $section_name = $sectionInfo->section_name;

            // Find section data in $sections collection
            $section_data = $sections->firstWhere('key', 'section_' . $section_id);

            // Initialize variables for section status and updated_at value
            $section_status = false;
            $updated_at_value = null;

            // Populate section status and updated_at if section data exists
            if ($section_data) {
                $section_status = $section_data->status;
                $updated_at_value = $section_data->updated_at;
            }

            // Construct section array and add to $data
            $section = [
                'section_id' => $section_id,
                'section_status' => $section_status,
                'updated_at' => $updated_at_value,
                'section_name' => $section_name,
            ];

            $data[] = $section;
        }

        // Initialize GFR values
        $GFR = [
            'ckd' => [
                'current_GFR' => '0', // Default values
                'basal_creatinine_GFR' => '0',
                'creatinine_on_discharge_GFR' => '0',
            ],
            'sobh' => [
                'current_GFR' => '0',
                'basal_creatinine_GFR' => '0',
                'creatinine_on_discharge_GFR' => '0',
            ],
        ];

        // Fetch answers related to GFR calculation
        $gender = Answers::where('patient_id', $patient_id)
            ->where('question_id', '8')->value('answer');

        $age = Answers::where('patient_id', $patient_id)
            ->where('question_id', '7')->value('answer');

        $height = Answers::where('patient_id', $patient_id)
            ->where('question_id', '140')->value('answer');

        $weight = Answers::where('patient_id', $patient_id)
            ->where('question_id', '141')->value('answer');

        $CurrentCreatinine = Answers::where('patient_id', $patient_id)
            ->where('question_id', '71')->value('answer');

        $BasalCreatinine = Answers::where('patient_id', $patient_id)
            ->where('question_id', '72')->value('answer');

        $CreatinineOnDischarge = Answers::where('patient_id', $patient_id)
            ->where('question_id', '80')->value('answer');

        // Check if all necessary parameters are present and valid
        if (!is_null($gender) && !is_null($age) && $age != 0 &&
            !is_null($height) && !is_null($weight) &&
            !is_null($CurrentCreatinine) && $CurrentCreatinine != 0 &&
            !is_null($BasalCreatinine) && $BasalCreatinine != 0 &&
            !is_null($CreatinineOnDischarge) && $CreatinineOnDischarge != 0) {

            // Convert to float values
            $c1 = floatval($CurrentCreatinine);
            $c2 = floatval($BasalCreatinine);
            $c3 = floatval($CreatinineOnDischarge);
            $ageValue = floatval($age);
            $heightValue = floatval($height);
            $weightValue = floatval($weight);
            $genderValue = $gender; // Assuming gender is not a numerical value

            // Calculate CKD GFR values
            $GFR['ckd']['current_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c1);
            $GFR['ckd']['basal_creatinine_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c2);
            $GFR['ckd']['creatinine_on_discharge_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c3);

            // Calculate Sobh GFR values
            $GFR['sobh']['current_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c1);
            $GFR['sobh']['basal_creatinine_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c2);
            $GFR['sobh']['creatinine_on_discharge_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c3);
        }

        // Log the final response information
        Log::info("Showing sections for patient ID: $patient_id", [
            'submit_status' => $submit_status,
            'patient_name' => $patient_name,
            'doctor_id' => $doctor_Id,
            'sections_count' => count($data),
            'gfr' => $GFR,
        ]);

        // Return JSON response
        return response()->json([
            'value' => true,
            'submit_status' => $submit_status,
            'patient_name' => $patient_name,
            'doctor_Id' => $doctor_Id,
            'gfr' => $GFR,
            'data' => $data,
        ]);
    }

}
