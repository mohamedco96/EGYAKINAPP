<?php

namespace App\Http\Controllers;

use App\Models\PatientHistory;
use App\Models\Patients;
use App\Http\Requests\StorePatientsRequest;
use App\Http\Requests\UpdatePatientsRequest;

use App\Models\PatientStatus;
use App\Models\Posts;
use App\Models\Questions;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\SectionsInfo;
use App\Models\User;
use App\Models\Answers;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOption\None;
use PDF;

class PatientsController extends Controller
{
    protected $Patients;

    public function __construct(Patients $Patients)
    {
        $this->Patients = $Patients;
    }


    /**
     * Display a listing of the resource.
     */
    public function homeGetAllData()
    {
        try {
            // Return all posts
            $posts = Posts::select('id', 'title', 'image', 'content', 'hidden', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->get();

            // Return current patients
            $user = Auth::user();
            $currentPatients = $user->patients()
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->latest('updated_at')
                ->limit(5) // Add limit here
                ->get();

            // Return all patients
            $allPatients = Patients::where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->latest('updated_at')
                ->limit(5) // Add limit here
                ->get();

            // Transform the response
            $transformPatientData = function ($patient) {
                $submit_status = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => optional($patient->answers->where('question_id', 1)->first())->answer,
                    'hospital' => optional($patient->answers->where('question_id', 2)->first())->answer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => $submit_status ?? false,
                        'outcome_status' => $outcomeStatus ?? false,
                    ]
                ];
            };

            $currentPatientsResponseData = $currentPatients->map($transformPatientData);


            // Transform the response
            $allPatientsResponseData = $allPatients->map($transformPatientData);

            // Get patient count and score value
            $userPatientCount = $user->patients()->count();
            $allPatientCount = Patients::count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool)$user->email_verified_at;

            // Get unread notification count
            $unreadCount = Notification::where('doctor_id', $user->id)
                ->where('read', false)->count();

            // Prepare response data
            $response = [
                'value' => true,
                'verified' => $isVerified,
                'unreadCount' => (string)$unreadCount,
                'doctor_patient_count' => (string)$userPatientCount,
                'all_patient_count' => (string)$allPatientCount,
                'score_value' => (string)$scoreValue,
                'role' => 'Admin',
                'data' => [
                    'all_patients' => $allPatientsResponseData,
                    'current_patient' => $currentPatientsResponseData,
                    'posts' => $posts,
                ],
            ];

            // Log successful response
            Log::info('Successfully retrieved home data.', ['user_id' => $user->id]);

            // Return the transformed response
            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error retrieving home data.', ['user_id' => optional(Auth::user())->id, 'exception' => $e]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve home data.',
            ], 500);
        }
    }

    public function doctorPatientGetAll()
    {
        try {
            // Return all patients
            $allPatients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->with(['status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status');
                }])
                ->with(['answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer', 'question_id');
                }])
                ->latest('updated_at')
                ->get();

            // Transform the response
            $transformedPatients = $allPatients->map(function ($patient) {
                $submitStatus = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
                $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => $nameAnswer,
                    'hospital' => $hospitalAnswer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => $submitStatus ?? false,
                        'outcome_status' => $outcomeStatus ?? false,
                    ]
                ];
            });

            // Paginate the transformed data
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $slicedData = $transformedPatients->slice(($currentPage - 1) * 10, 10);
            $transformedPatientsPaginated = new LengthAwarePaginator($slicedData->values(), count($transformedPatients), 10);

            // Prepare response data
            $response = [
                'value' => true,
                'data' => $transformedPatientsPaginated,
            ];

            // Log successful response
            Log::info('Successfully retrieved all patients for doctor.', ['doctor_id' => optional(auth()->user())->id]);

            // Return the transformed response
            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error retrieving all patients for doctor.', ['doctor_id' => optional(auth()->user())->id, 'exception' => $e]);

            // Return error response
            return response()->json(['error' => 'Failed to retrieve all patients for doctor.'], 500);
        }
    }

    public function doctorPatientGet()
    {
        try {
            // Return all patients
            $user = Auth::user();
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->with(['status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status');
                }])
                ->with(['answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer', 'question_id');
                }])
                ->latest('updated_at')
                ->get();

            // Transform the response
            $transformedPatients = $currentPatients->map(function ($patient) {
                $submitStatus = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
                $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => $nameAnswer,
                    'hospital' => $hospitalAnswer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => $submitStatus ?? false,
                        'outcome_status' => $outcomeStatus ?? false,
                    ]
                ];
            });

            // Paginate the transformed data
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $slicedData = $transformedPatients->slice(($currentPage - 1) * 10, 10);
            $transformedPatientsPaginated = new LengthAwarePaginator($slicedData->values(), count($transformedPatients), 10);

            $userPatientCount = $user->patients()->count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool)$user->email_verified_at;

            // Prepare response data
            $response = [
                'value' => true,
                'verified' => $isVerified,
                'patient_count' => strval($userPatientCount),
                'score_value' => strval($scoreValue),
                'data' => $transformedPatientsPaginated,
            ];

            // Log successful response
            Log::info('Successfully retrieved all patients for doctor.', ['doctor_id' => optional(auth()->user())->id]);

            // Return the transformed response
            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error retrieving all patients for doctor.', ['doctor_id' => optional(auth()->user())->id, 'exception' => $e]);

            // Return error response
            return response()->json(['error' => 'Failed to retrieve all patients for doctor.'], 500);
        }
    }


    public function doctorPatientGetold()
    {
        try {
            $user = Auth::user();
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->with(['status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status')
                        ->where(function ($query) {
                            $query->where('key', 'LIKE', 'submit_status')
                                ->orWhere('key', 'LIKE', 'outcome_status');
                        });
                }])
                ->with(['answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer')
                        ->whereIn('question_id', [1, 2]); // Adjusted condition using whereIn
                }])
                ->latest('updated_at')
                ->paginate(10);

            $userPatientCount = $user->patients()->count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool)$user->email_verified_at;

            $response = [
                'value' => true,
                'verified' => $isVerified,
                'patient_count' => strval($userPatientCount),
                'score_value' => strval($scoreValue),
                'data' => $currentPatients,
            ];

            // Log successful response
            Log::info('Successfully retrieved current patient data for ', ['doctor_id' => optional(Auth::user())->id]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error retrieving doctor patient data.', ['doctor_id' => optional(Auth::user())->id, 'exception' => $e]);

            // Return error response
            return response()->json(['error' => 'Failed to retrieve doctor patient data.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            $doctor_id = Auth::id();

            // Retrieve question IDs and their corresponding section IDs from the database
            $questionSectionIds = Questions::pluck('section_id', 'id')->toArray();

            // Create a new patient record
            $patient = Patients::create([
                'doctor_id' => $doctor_id,
            ]);

            // Iterate over the request data to handle questions dynamically
            foreach ($request->all() as $key => $value) {
                // Check if the key represents a question and matches the expected format (e.g., "14")
                if (preg_match('/^\d+$/', $key)) {
                    // Extract the question ID from the key
                    $questionId = (int)$key;

                    // Retrieve the section ID for the question from $questionSectionIds array
                    $sectionId = $questionSectionIds[$questionId] ?? null;

                    // Check if the question has already been processed
                    if (isset($value['answers']) && is_array($value['answers'])) {
                        // Process the answer for the question
                        $answers = $value['answers'];
                        $otherFieldAnswer = $value['other_field'] ?? null;

                        $this->saveAnswer($doctor_id, $questionId, $answers, $patient->id, false, $sectionId);
                        $this->saveAnswer($doctor_id, $questionId, $otherFieldAnswer, $patient->id, true, $sectionId);
                    } elseif (isset($questionSectionIds[$questionId])) {
                        // Save the answer along with the corresponding section ID
                        $this->saveAnswer($doctor_id, $questionId, $value, $patient->id, false, $sectionId);
                    }
                }
            }

            // Create patient status records
            PatientStatus::create(
                [
                    'doctor_id' => $doctor_id,
                    'patient_id' => $patient->id,
                    'key' => 'section_' . ($questionSectionIds[1] ?? null),
                    'status' => true
                ]
            );
            PatientStatus::create(
                [
                    'doctor_id' => $doctor_id,
                    'patient_id' => $patient->id,
                    'key' => 'submit_status',
                    'status' => false
                ]
            );

            // Logging successful patient creation
            Log::info('New patient created', ['doctor_id' => $doctor_id, 'patient_id' => $patient->id]);

            // Notifying other doctors
            $doctorIds = User::whereNotIn('id', [$doctor_id])->pluck('id');
            foreach ($doctorIds as $otherDoctorId) {
                Notification::create([
                    'content' => 'New Patient was created',
                    'read' => false,
                    'type' => 'New Patient',
                    'patient_id' => $patient->id,
                    'doctor_id' => $otherDoctorId,
                ]);
            }

            // Retrieve patient name
            $patientName = Answers::where('patient_id', $patient->id)
                ->where('question_id', '1')
                ->value('answer');

            // Commit the transaction
            DB::commit();

            $response = [
                'value' => true,
                'doctor_id' => $doctor_id,
                'id' => $patient->id,
                'name' => $patientName,
                'submit_status' => false,
                'message' => 'Patient Created Successfully',
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollback();

            // Handle and log errors
            Log::error("Error while storing patient: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientsRequest $request, $section_id, $patient_id)
    {
        // Check if the section exists
        $sectionExists = Questions::where('section_id', $section_id)->exists();
        if (!$sectionExists) {
            return response()->json([
                'value' => false,
                'message' => "Section not found",
            ], 404);
        }

        $patient = Patients::find($patient_id); // Retrieve the patient from the database
        if (!$patient) {
            return response()->json([
                'value' => false,
                'message' => "Patient not found",
            ], 404);
        }
        try {
            // Start a database transaction
            DB::beginTransaction();

            $doctor_id = Auth::id();

            // Retrieve question IDs and their corresponding section IDs from the database
            $questionSectionIds = Questions::pluck('section_id', 'id')->toArray();

            $patientSectionStatus = PatientStatus::where('patient_id', $patient_id)
                ->where('key', 'section_' . $section_id)->first();

            if ($patientSectionStatus) {
                // Iterate over the request data to handle questions dynamically
                foreach ($request->all() as $key => $value) {
                    // Check if the key represents a question and matches the expected format (e.g., "14")
                    if (preg_match('/^\d+$/', $key)) {
                        // Extract the question ID from the key
                        $questionId = (int)$key;

                        // Retrieve the section ID for the question from $questionSectionIds array
                        $sectionId = $questionSectionIds[$questionId] ?? null;

                        // Check if the question has already been processed
                        if (isset($value['answers']) && is_array($value['answers'])) {
                            // Process the answer for the question
                            $answers = $value['answers'];
                            $otherFieldAnswer = $value['other_field'] ?? null;

                            $this->updateAnswer($questionId, $answers, $patient_id, false, $section_id);
                            $this->updateAnswer($questionId, $otherFieldAnswer, $patient_id, true, $section_id);
                        } elseif (isset($questionSectionIds[$questionId])) {
                            // Save the answer along with the corresponding section ID
                            $this->updateAnswer($questionId, $value, $patient_id, false, $section_id);
                        }
                    }
                    $patientSectionStatus->update(['updated_at' => now()]);
                }

            } else {
                // Iterate over the request data to handle questions dynamically
                foreach ($request->all() as $key => $value) {
                    // Check if the key represents a question and matches the expected format (e.g., "14")
                    if (preg_match('/^\d+$/', $key)) {
                        // Extract the question ID from the key
                        $questionId = (int)$key;

                        // Retrieve the section ID for the question from $questionSectionIds array
                        $sectionId = $questionSectionIds[$questionId] ?? null;

                        // Check if the question has already been processed
                        if (isset($value['answers']) && is_array($value['answers'])) {
                            // Process the answer for the question
                            $answers = $value['answers'];
                            $otherFieldAnswer = $value['other_field'] ?? null;

                            $this->saveAnswer($doctor_id, $questionId, $answers, $patient_id, false, $section_id);
                            $this->saveAnswer($doctor_id, $questionId, $otherFieldAnswer, $patient_id, true, $section_id);
                        } elseif (isset($questionSectionIds[$questionId])) {
                            // Save the answer along with the corresponding section ID
                            $this->saveAnswer($doctor_id, $questionId, $value, $patient_id, false, $section_id);
                        }
                    }
                }

                // Create patient status records
                PatientStatus::create([
                    'doctor_id' => $doctor_id,
                    'patient_id' => $patient_id,
                    'key' => 'section_' . ($section_id ?? null),
                    'status' => true
                ]);
            }


            // Logging successful patient creation
            Log::info('Section_' . $section_id . 'updated successfully', ['doctor_id' => $doctor_id, 'patient_id' => $patient_id]);


            // Commit the transaction
            DB::commit();

            // Response with success message and any additional data
            $response = [
                'value' => true,
                'message' => 'Section updated successfully.',
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Rollback the transaction
            DB::rollback();

            // Handle and log errors
            Log::error("Error while storing patient: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function saveAnswer($doctor_id, $questionId, $answerText, $patientId, $isOtherField = false, $sectionId = null)
    {
        // Create a new answer record
        Answers::create([
            'doctor_id' => $doctor_id,
            'section_id' => $sectionId, // Pass section ID
            'question_id' => $questionId,
            'patient_id' => $patientId,
            'answer' => is_array($answerText) ? $answerText : $answerText, // Convert array to JSON string if it's an array
            // Use 'other_field' column if $isOtherField is true, otherwise use null
            'type' => $isOtherField ? 'other' : null,
        ]);
    }

    protected function updateAnswer($questionId, $answerText, $patientId, $isOtherField = false, $sectionId = null)
    {
        if ($isOtherField) {
            // update other answer record
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->whereNotNull('type')
                ->update([
                    'answer' => is_array($answerText) ? $answerText : $answerText, // Convert array to JSON string if it's an array
                    // Use 'other_field' column if $isOtherField is true, otherwise use null
                    'type' => $isOtherField ? 'other' : null,
                ]);
        } else {
            // update answer record
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->where('type', null)
                ->update([
                    'answer' => is_array($answerText) ? $answerText : $answerText, // Convert array to JSON string if it's an array
                    // Use 'other_field' column if $isOtherField is true, otherwise use null
                    'type' => $isOtherField ? 'other' : null,
                ]);
        }
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

            $patient = Patients::find($patient_id); // Retrieve the patient from the database
            if (!$patient) {
                return response()->json([
                    'value' => false,
                    'message' => "Patient not found",
                ], 404);
            }

            $data = [];

            // Fetch questions dynamically based on section_id
            $questions = Questions::where('section_id', $section_id)
                ->orderBy('id')
                ->get();

            // Fetch all answers for the patient in one query
            $answers = Answers::where('patient_id', $patient_id)
                ->get();

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

                // Get the IDs of questions of type 'multiple'
                $multipleQuestionIds = $questions->filter(function ($question) {
                    return $question->type === 'multiple';
                })->pluck('id')->toArray();

                $multipleQuestionAnswers = Answers::whereIn('question_id', $multipleQuestionIds)
                    ->where('patient_id', $patient_id)
                    ->get();

                if ($question->type === 'multiple') {
                    // Initialize the answer array
                    $questionData['answer'] = [
                        'answers' => [], // Initialize answers as an empty array
                        'other_field' => null // Set other_field to null by default
                    ];
                    // Find answers for this question from the fetched answers
                    $questionAnswers = $multipleQuestionAnswers->where('question_id', $question->id);

                    // Populate the answers array
                    foreach ($questionAnswers as $answer) {
                        if ($answer->type !== 'other') {
                            $questionData['answer']['answers'][] = $answer->answer;
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

            $response = [
                'value' => true,
                'data' => $data,
            ];

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
    public function show($patient_id)
    {
        $submit_status = PatientStatus::where('patient_id', $patient_id)
            ->where('key', 'submit_status')
            ->value('status');


        $sections = PatientStatus::select('key', 'status', 'updated_at')
            ->where('patient_id', $patient_id)
            ->where('key', 'LIKE', 'section_%')
            ->get();

        $patient_name = Answers::where('patient_id', $patient_id)
            ->where('question_id', '1')
            ->value('answer');

        $doctor_Id = Patients::where('id', $patient_id)->value('doctor_id');

        if (!$patient_name) {
            return response()->json([
                'value' => false,
                'message' => 'Patient not found for the given patient ID.',
            ], 404);
        }

        if (!$sections) {
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }

        $sectionInfos = SectionsInfo::all();

        $data = [];
        foreach ($sectionInfos as $sectionInfo) {
            if ($sectionInfo->id === 8){
                continue;
            }
            $section_id = $sectionInfo->id;
            $section_name = $sectionInfo->section_name;

            $section_data = $sections->firstWhere('key', 'section_' . $section_id);

            if (!$section_data) {
                $updated_at_value = null;
                $section_status = false;
            } else {
                $updated_at_value = $section_data->updated_at;
                $section_status = $section_data->status;
            }


            $section = [
                'section_id' => $section_id,
                'section_status' => $section_status,
                'updated_at' => $updated_at_value,
                'section_name' => $section_name,
            ];

            $data[] = $section;
        }

        if ($sections) {
            return response()->json([
                'value' => true,
                'submit_status' => $submit_status,
                'patient_name' => $patient_name,
                'doctor_Id' => $doctor_Id,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'value' => false,
                'message' => 'Sections not found for the given patient ID.',
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        // Check if the user has permission to edit articles
        if ($user->hasPermissionTo('delete patient', 'web')) {
            $Patient = Patients::find($id);

            if ($Patient != null) {
                Patients::destroy($id);

                $response = [
                    'value' => true,
                    'message' => 'Patient Deleted Successfully',
                ];

                return response($response, 200);
            } else {
                $response = [
                    'value' => false,
                    'message' => 'No Patient was found',
                ];

                return response($response, 404);
            }
        } else {
            return response()->json(['value' => false,
                'message' => 'User does not have permission to delete Patient']
                , 404);
        }

    }

    public function searchNew($name)
    {
        //return 'test';
        try {
            $patients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->where(function ($query) use ($name) {
                    $query
                        ->WhereHas('doctor', function ($query) use ($name) {
                            $query->where('name', 'like', '%' . $name . '%');
                        })
                        ->orWhereHas('answers', function ($query) use ($name) {
                            $query->where('answer', 'like', '%' . $name . '%');
                        });

                })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image');
                }])
                ->with(['status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status');
                }])
                ->with(['answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer', 'question_id');
                }])
                ->latest('updated_at')
                ->get();

            // Transform the response
            $transformedPatients = $patients->map(function ($patient) {
                $submitStatus = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
                $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => $nameAnswer,
                    'hospital' => $hospitalAnswer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => $submitStatus ?? false,
                        'outcome_status' => $outcomeStatus ?? false,
                    ]
                ];
            });

            // Paginate the transformed data
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $slicedData = $transformedPatients->slice(($currentPage - 1) * 10, 10);
            $transformedPatientsPaginated = new LengthAwarePaginator($slicedData->values(), count($transformedPatients), 10);


            if ($patients->isEmpty()) {
                // Log no patient found
                Log::info('No patient was found for the search term.', ['search_term' => $name]);

                $response = [
                    'value' => false,
                    'message' => 'No patient was found.',
                ];

                return response()->json($response, 404);
            }

            // Log successful search
            Log::info('Successfully retrieved patients for the search term.', ['search_term' => $name]);

            $response = [
                'value' => true,
                'data' => $transformedPatientsPaginated,
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for patients.', ['search_term' => $name, 'exception' => $e]);

            $response = [
                'value' => false,
                'message' => 'Failed to search for patients.',
            ];
            // Return error response
            return response()->json($response, 500);
        }
    }

    public function generatePatientPDF($patient_id)
    {
        try {
            // Retrieve the patient from the database with related data
            $patient = Patients::with(['doctor', 'status', 'answers'])->findOrFail($patient_id);

            $data = [];

            // Fetch all questions
            $questions = Questions::with('section')->get();

            // Iterate over each question
            foreach ($questions as $question) {
                // Skip questions with certain IDs
                if ($question->skip) {
                    Log::info("Question with ID {$question->id} skipped as per skip flag.");
                    continue;
                }

                $questionData = [
                    'id' => $question->id,
                    'section_id' => $question->section->id ?? "test",
                    'section_name' => $question->section->section_name ?? "Not found",
                    'question' => $question->question,
                    'values' => $question->values,
                    'type' => $question->type,
                    'keyboard_type' => $question->keyboard_type,
                    'mandatory' => $question->mandatory,
                    'updated_at' => $question->updated_at,
                ];

                // Find the answer for this question
                $answer = $patient->answers->where('question_id', $question->id)->first();

                if ($question->type === 'multiple') {
                    // Initialize the answer array
                    $questionData['answer'] = [
                        'answers' => [], // Initialize answers as an empty array
                        'other_field' => null // Set other_field to null by default
                    ];

                    // Find answers for this question
                    $questionAnswers = $patient->answers->where('question_id', $question->id);

                    // Populate the answers array
                    foreach ($questionAnswers as $answer) {
                        if ($answer->type !== 'other') {
                            $questionData['answer']['answers'][] = $answer->answer;
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

            // Pass the data to the blade view
            $pdfData = [
                'patient' => $patient,
                'questionData' => $data
                // Add more data here if needed
            ];

            // Generate the PDF using the blade view and data
            $pdf = PDF::loadView('patient_pdf', $pdfData);

            // Ensure the 'pdfs' directory exists in the public disk
            Storage::disk('public')->makeDirectory('pdfs');

            // Generate a unique filename for the PDF
            $pdfFileName = 'filename2.pdf';

            // Save the PDF file to the public disk
            Storage::disk('public')->put('pdfs/' . $pdfFileName, $pdf->output());

            // Generate the URL for downloading the PDF file
            $pdfUrl = config('app.url') . '/' . 'storage/app/public/pdfs/' . $pdfFileName;

            // Return the URL to download the PDF file along with patient data
            return response()->json(['pdf_url' => $pdfUrl, 'patient' => $data]);
        } catch (\Exception $e) {
            // Log and return error if an exception occurs
            Log::error("Error while generating PDF: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generatePatientPDFold($patient_id)
    {
        // Retrieve the patient from the database
        //$patient = Patients::findOrFail($patient_id);

        $patient = Patients::select('id', 'doctor_id', 'updated_at')
            ->where('hidden', false)
            ->where('id',132)
            ->with(['doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'image');
            }])
            ->with(['status' => function ($query) {
                $query->select('id', 'patient_id', 'key', 'status');
            }])
            ->with(['answers' => function ($query) {
                $query->select('id', 'patient_id', 'answer', 'question_id');
            }])
            ->latest('updated_at')
            ->get();

        // Pass the data to the blade view
        $data = [
            'patient' => $patient,
            // Add more data here if needed
        ];

        // Generate the PDF using the blade view and data
        $pdf = PDF::loadView('patient_pdf', $data);

        //$pdf = PDF::loadHTML('<h1>Hello, this is a New PDF!</h1>');

        // Ensure the 'pdfs' directory exists in the public disk
        Storage::disk('public')->makeDirectory('pdfs');

        // Generate a unique filename for the PDF
        $pdfFileName = 'filename2.pdf';

        // Save the PDF file to the public disk
        Storage::disk('public')->put('pdfs/' . $pdfFileName, $pdf->output());

        // Generate the URL for downloading the PDF file
        // $pdfUrl = Storage::disk('public')->url('pdfs/' . $pdfFileName);
        $pdfUrl = config('app.url') . '/' . 'storage/app/public/pdfs/' . $pdfFileName;

        // Return the URL to download the PDF file
        return response()->json(['pdf_url' => $pdfUrl]);
    }


}
