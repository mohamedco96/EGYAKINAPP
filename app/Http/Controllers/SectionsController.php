<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Patients;
use App\Http\Requests\UpdatePatientsRequest;
use App\Models\PatientStatus;
use App\Models\Questions;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\SectionsInfo;
use App\Models\Answers;
use App\Models\User;
use App\Notifications\ReachingSpecificPoints;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
     * @param string|null $gender
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
        // Check for null values
        if (is_null($age) || is_null($weight) || is_null($height) || is_null($serumCreatinine)) {
            return 0;
        }

        // Check if serumCreatinine is zero to avoid division by zero
        if ($serumCreatinine == 0) {
            return 0; // or you can throw an exception or return an error message
        }else{
            $ccr = ((140 - $age) / $serumCreatinine) *
                pow($weight, 0.54) *
                pow($height, 0.40) *
                0.014;

            return number_format($ccr, 2, '.', '');
        }



    }


    /**
     * Calculate GFR using MDRD equation.
     *
     * @param float $serumCr Serum Creatinine level
     * @param int $age Age of the patient
     * @param bool $isBlack Whether the patient is black (true/false)
     * @param bool $isFemale Whether the patient is female (true/false)
     * @return float Calculated GFR
     */
    protected function calculateGFRforMDRD($serumCr, $age, $race, $gender)
    {
        if ($gender === 'Female') {
            $genderFactor = 0.742;
        } else {
            $genderFactor = 1.0;
        }

        if ($race === 'yes') {
            $raceFactor = 1.212;
        } else {
            $raceFactor = 1.0;
        }

        $constant = 175.0;
        $ageFactor = pow($age, -0.203);
        $serumCrFactor = pow($serumCr, -1.154);

        $mdrd = $constant * $serumCrFactor * $ageFactor * $raceFactor * $genderFactor;

        return number_format($mdrd, 2, '.', '');

    }

    /**
     * Update the final submit status for a patient.
     *
     * @param UpdatePatientsRequest $request
     * @param int $patient_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFinalSubmit(UpdatePatientsRequest $request, $patient_id)
    {
        try {
            // Fetch patient submit status
            $patientSubmitStatus = PatientStatus::where('patient_id', $patient_id)
                ->where('key', 'submit_status')
                ->first();

            // Handle case where patient submit status is not found
            if (!$patientSubmitStatus) {
                Log::error("Patient submit status not found for patient ID: $patient_id");
                return response()->json([
                    'value' => false,
                    'message' => 'Patient not found',
                ], 404);
            }

            // Update submit status to true
            $patientSubmitStatus->update(['status' => true]);

            // Scoring system
            $doctorId = Auth::id();
            $incrementAmount = 4;
            $action = 'Final Submit';

            // Fetch or create score record for the doctor
            $score = Score::firstOrNew(['doctor_id' => $doctorId]);
            $score->score += $incrementAmount;
            $score->threshold += $incrementAmount;
            $newThreshold = $score->threshold;

            $user = Auth::user();

            // Send notification if score threshold reaches 50 or its multiples
            if ($newThreshold >= 50) {
                $user->notify(new ReachingSpecificPoints($score));
                $score->threshold = 0;
            }

            $score->save();

            // Log score history
            ScoreHistory::create([
                'doctor_id' => $doctorId,
                'score' => $incrementAmount,
                'action' => $action,
                'patient_id' => $patient_id,
                'timestamp' => now(),
            ]);

            Patients::where('id', $patient_id)
                ->update([
                    'updated_at' => now(),
                ]);

//            $this->checkAndAssignAchievements($user);

            // Return success response
            $response = [
                'value' => true,
                'message' => 'Final Submit Updated Successfully',
            ];

            return response()->json($response, 201);
        } catch (\Exception $e) {
            // Log and return error response
            Log::error("Error updating final submit for patient ID: $patient_id. Error: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error updating final submit.',
            ], 500);
        }
    }


//    public function checkAndAssignAchievements(User $user)
//    {
//        $userScore = $user->score->score;
//        $achievements = Achievement::all();
//
//        foreach ($achievements as $achievement) {
//            $existingAchievement = $user->achievements()->where('achievement_id', $achievement->id)->first();
//            if (!$existingAchievement) {
//                $achieved = $userScore >= $achievement->score;
//                $user->achievements()->attach($achievement->id, ['achieved' => $achieved]);
//            } elseif ($existingAchievement->pivot->achieved == false && $userScore >= $achievement->score) {
//                $user->achievements()->updateExistingPivot($achievement->id, ['achieved' => true]);
//            }
//        }
//    }

    /**
     * Show questions and answers for a specific section and patient.
     *
     * @param int $section_id
     * @param int $patient_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showQuestionsAnswers($section_id, $patient_id)
    {
        try {
            // Check if the section exists
            $sectionExists = Questions::where('section_id', $section_id)->exists();
            if (!$sectionExists) {
                return response()->json([
                    'value' => true,
                    'data' => [],
                ], 200);
            }
    
            // Fetch questions for the specified section
            $questions = Questions::where('section_id', $section_id)
                ->orderBy('sort')
                ->get();
    
            // Fetch all answers for the patient related to these questions
            $answers = Answers::where('patient_id', $patient_id)
                ->whereIn('question_id', $questions->pluck('id'))
                ->get();
    
            // Initialize array to store questions and answers
            $data = [];
    
            foreach ($questions as $question) {
                // Skip questions flagged with 'skip'
                if ($question->skip) {
                    Log::info("Question with ID {$question->id} skipped as per skip flag.");
                    continue;
                }
    
                // Find answer for this question
                $answer = $answers->where('question_id', $question->id)->first();
    
                // Skip hidden questions with no answer
                if ($question->hidden && !$answer) {
                    Log::info("Hidden question with ID {$question->id} skipped due to no answer.");
                    continue;
                }
    
                // Prepare question data
                $questionData = [
                    'id' => $question->id,
                    'question' => $question->question,
                    'values' => $question->values,
                    'type' => $question->type,
                    'keyboard_type' => $question->keyboard_type,
                    'mandatory' => $question->mandatory,
                    'hidden' => $question->hidden,
                    'updated_at' => $question->updated_at,
                ];
    
                // Handle different question types
                if ($question->type === 'select') {
                    $questionData['answer'] = [
                        'answers' => null,
                        'other_field' => null,
                    ];
    
                    if ($answer) {
                        $questionData['answer']['answers'] = $answer->answer; // Set the selected value
                        if ($answer->type === 'other') {
                            $questionData['answer']['other_field'] = $answer->answer; // Set the other field value if present
                        }
                    }
                } elseif ($question->type === 'multiple') {
                    $questionData['answer'] = [
                        'answers' => [],
                        'other_field' => null,
                    ];
    
                    $questionAnswers = $answers->where('question_id', $question->id);
                    foreach ($questionAnswers as $ans) {
                        if ($ans->type !== 'other') {
                            $questionData['answer']['answers'] = $ans->answer;
                        }
                        if ($ans->type === 'other') {
                            $questionData['answer']['other_field'] = $ans->answer;
                        }
                    }
                } elseif ($question->type === 'files') {
                    $questionData['answer'] = [];
    
                    if ($answer === null) {
                        $questionData['answer'] = [];
                    } else {
                        $filePaths = json_decode($answer->answer);
    
                        if (is_array($filePaths)) {
                            foreach ($filePaths as $filePath) {
                                $absolutePath = Storage::disk('public')->url($filePath);
                                $questionData['answer'][] = $absolutePath;
                            }
                        }
                    }
                } else {
                    // For other types, directly set the answer
                    $questionData['answer'] = $answer ? $answer->answer : null;
                }
    
                // Add question data to main data array
                $data[] = $questionData;
            }
    
            // Fetch submitter information for section 8
            $submitter = PatientStatus::select('id', 'doctor_id')
                ->where('patient_id', $patient_id)
                ->where('key', 'outcome_status')
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
                }])
                ->first();
    
            // Prepare response based on section 8 or other sections
            if ($section_id == 8) {
                if ($submitter && $submitter->doctor) {
                    $doctor = $submitter->doctor;
                    $response = [
                        'value' => true,
                        'Submitter' => [
                            'id' => (string) optional($submitter)->doctor_id,
                            'name' => (optional($doctor)->name && optional($doctor)->lname)
                                ? optional($doctor)->name . ' ' . optional($doctor)->lname
                                : null,
                            'image' => optional($doctor)->image,
                            'syndicateCard' => optional($doctor)->isSyndicateCardRequired,
                        ],
                        'data' => $data,
                    ];
                } else {
                    $response = [
                        'value' => true,
                        'Submitter' => [
                            'name' => null,
                            'image' => null,
                        ],
                        'data' => $data,
                    ];
                }
            } else {
                $response = [
                    'value' => true,
                    'data' => $data,
                ];
            }
    
            // Log successful retrieval of questions and answers
            Log::info("Questions and answers retrieved successfully for section ID {$section_id} and patient ID {$patient_id}.");
    
            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log and return error response
            Log::error("Error while fetching questions and answers: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    
    

    /**
     * Show sections and their statuses for a patient.
     *
     * @param int $patient_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showSections($patient_id)
    {
        try {
            // Fetch patient submit status
            $submit_status = PatientStatus::where('patient_id', $patient_id)
                ->where('key', 'submit_status')
                ->value('status');

            // Fetch sections data related to the patient
            $sections = PatientStatus::select('key', 'status', 'updated_at')
                ->where('patient_id', $patient_id)
                ->where('key', 'LIKE', 'section_%')
                ->get();

            // Fetch patient name and doctor ID
            $patient_name = Answers::where('patient_id', $patient_id)
                ->where('question_id', '1')
                ->value('answer');

            $doctor_Id = Patients::where('id', $patient_id)->value('doctor_id');

            // Handle cases where patient name or sections are not found
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

            // Initialize array for storing section information
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
                    'current_GFR' => '0',
                    'basal_creatinine_GFR' => '0',
                    'creatinine_on_discharge_GFR' => '0',
                ],
                'sobh' => [
                    'current_GFR' => '0',
                    'basal_creatinine_GFR' => '0',
                    'creatinine_on_discharge_GFR' => '0',
                ],
                'mdrd' => [
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

            $race = Answers::where('patient_id', $patient_id)
                ->where('question_id', '149')->value('answer');

// Convert to float values
            $c1 = floatval($CurrentCreatinine);
            $c2 = floatval($BasalCreatinine);
            $c3 = floatval($CreatinineOnDischarge);
            $ageValue = floatval($age);
            $heightValue = floatval($height);
            $weightValue = floatval($weight);
            $genderValue = $gender; // Assuming gender is not a numerical value
            $raceValue = $race;

// Check if all necessary parameters are present and valid
            if (!is_null($gender) && !is_null($age) && $age != 0 &&
                !is_null($height) && $height != 0 && !is_null($weight) && $weight != 0 &&
                !is_null($race) && !is_null($CurrentCreatinine) && $CurrentCreatinine != 0) {

                // Calculate CKD GFR values
                $GFR['ckd']['current_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c1);

                // Calculate Sobh GFR values
                $GFR['sobh']['current_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c1);

                // Calculate MDRD GFR
                $GFR['mdrd']['current_GFR'] = $this->calculateGFRforMDRD($c1, $ageValue, $raceValue, $genderValue);
            }

            if (!is_null($gender) && !is_null($age) && $age != 0 &&
                !is_null($height) && $height != 0 && !is_null($weight) && $weight != 0 &&
                !is_null($race) && !is_null($BasalCreatinine) && $BasalCreatinine != 0) {

                // Calculate CKD GFR values
                $GFR['ckd']['basal_creatinine_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c2);

                // Calculate Sobh GFR values
                $GFR['sobh']['basal_creatinine_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c2);

                // Calculate MDRD GFR
                $GFR['mdrd']['basal_creatinine_GFR'] = $this->calculateGFRforMDRD($c2, $ageValue, $raceValue, $genderValue);
            }

            if (!is_null($gender) && !is_null($age) && $age != 0 &&
                !is_null($height) && $height != 0 && !is_null($weight) && $weight != 0 &&
                !is_null($race) && !is_null($CreatinineOnDischarge) && $CreatinineOnDischarge != 0) {

                // Calculate CKD GFR values
                $GFR['ckd']['creatinine_on_discharge_GFR'] = $this->calculateGFRForCKD($genderValue, $ageValue, $c3);

                // Calculate Sobh GFR values
                $GFR['sobh']['creatinine_on_discharge_GFR'] = $this->calculateSobhCcr($ageValue, $weightValue, $heightValue, $c3);

                // Calculate MDRD GFR
                $GFR['mdrd']['creatinine_on_discharge_GFR'] = $this->calculateGFRforMDRD($c3, $ageValue, $raceValue, $genderValue);
            }

            // Log successful retrieval of sections information
            Log::info("Showing sections for patient ID: $patient_id", [
                'submit_status' => $submit_status,
                'patient_name' => $patient_name,
                'doctor_id' => $doctor_Id,
                'sections_count' => count($data),
                'gfr' => $GFR,
            ]);

            // Return JSON response with sections information
            return response()->json([
                'value' => true,
                'submit_status' => $submit_status,
                'patient_name' => $patient_name,
                'doctor_Id' => (string) $doctor_Id,
                'gfr' => $GFR,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            // Log and return error response
            Log::error("Error while showing sections for patient ID: $patient_id. Error: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }


}

