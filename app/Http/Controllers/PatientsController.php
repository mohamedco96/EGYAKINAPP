<?php

namespace App\Http\Controllers;

use App\Events\SearchResultsUpdated;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\ConsultationDoctor;
use App\Models\Dose;
use App\Models\FcmToken;
use App\Models\Patients;
use App\Http\Requests\UpdatePatientsRequest;
use App\Models\PatientStatus;
use App\Models\Posts;
use App\Models\Questions;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Models\User;
use App\Models\Answers;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDF;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AchievementController;
use function PHPUnit\Framework\assertNotTrue;


class PatientsController extends Controller
{
    protected $notificationController;
    protected $patients;
    protected $achievement;


    public function __construct(NotificationController $notificationController, Patients $patients, AchievementController $achievement)
    {
        $this->notificationController = $notificationController;
        $this->patients = $patients;
        $this->achievement = $achievement;

    }

    /**
     * Handle the file upload.
     */
    public function uploadFile(Request $request)
    {
        // Validate the request
        $request->validate([
            //'file' => 'required|mimes:jpg,jpeg,png,pdf|max:2048', // Example validation
        ]);

        // Check if the request has a file
        if ($request->hasFile('file')) {
            // Get the file
            $file = $request->file('file');

            // Get the original filename
            $filename = $file->getClientOriginalName();

            // Store the file in the storage/app/uploads directory
            $path = $file->storeAs('reports', random_int(500,10000000000) .'_'. $filename, 'public');

            // Get the full URL of the uploaded file
            $relativePath = 'storage/' . $path;

            $fileUrl = config('app.url') . '/' . 'storage/' . $path;

            // Store file path in database if necessary
            // Example: File::create(['path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'file' => $filename,
                'path' => $path,
                'full_path' => $fileUrl,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please select a file to upload.',
        ], 400);
    }


    public function uploadFileNew(Request $request)
    {
        // Initialize an array to store the file URLs grouped by keys
        $fileUrls = [];

        // Loop through each key in the request
        foreach ($request->all() as $key => $files) {
            // Check if the value is an array of files
            if (is_array($files)) {
                foreach ($files as $file) {
                    // Get file data and name from the request
                    $fileData = $file['file_data'];
                    $fileName = $file['file_name'];

                    // Check if both file name and data are present
                    if (!$fileData || !$fileName) {
                        return response()->json([
                            'message' => 'File name or data is missing',
                        ], 400);
                    }

                    // Decode base64 data
                    $fileContent = base64_decode($fileData);

                    // Define the path to save the file in the medical_reports folder
                    $filePath = 'medical_reports/' . $fileName;

                    // Save file to storage or public folder
                    Storage::disk('public')->put($filePath, $fileContent);

                    // Generate file URL
                    $fileUrl = Storage::disk('public')->url($filePath);

                    // Add the file URL to the response array grouped by key
                    $fileUrls[$key][] = $fileUrl;
                }
            }
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'file_urls' => $fileUrls,
        ]);
    }
    /**
     * Display a listing of the resource.
     */
    public function homeGetAllData()
    {
        try {
            // Retrieve the authenticated user
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Return all posts
            $posts = Posts::select('id', 'title', 'image', 'content', 'hidden', 'post_type', 'webinar_date', 'url', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
                }])
                ->get();

            // Return current patients
            $currentPatients = $user->patients()
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false);
                })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
                }])
                ->latest('updated_at')
                ->limit(5)
                ->get();

            // Return all patients
            $allPatients = Patients::when(!$isAdminOrTester, function ($query) {
                return $query->where('hidden', false);
            })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
                }])
                ->latest('updated_at')
                ->limit(5)
                ->get();

            // Return Top Doctors
            $topDoctors = User::select('id', 'name', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version')
                ->withCount('patients')
                ->selectSub(function ($query) {
                    $query->selectRaw('COALESCE(score, 0)')
                        ->from('scores')
                        ->whereColumn('users.id', 'scores.doctor_id')
                        ->limit(1);
                }, 'score')
                ->orderByRaw('patients_count DESC, COALESCE(score, 0) DESC')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    $user->patients_count = strval($user->patients_count);
                    return $user;
                });

            // Return doctors with Pending Syndicate Card only for Admin or Tester
            $pendingSyndicateCard = $isAdminOrTester
                ? User::select('id', 'name', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                    ->where('isSyndicateCardRequired', 'Pending')
                    ->limit(10)
                    ->get()
                : collect(); // Return empty list for other users

            // Transform the patient data
            $transformPatientData = function ($patient) {
                $submit_status = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                // Get doctor_id of the submitter from outcome status
                $outcomeSubmitterDoctorId = optional($patient->status->where('key', 'outcome_status')->first())->doctor_id;

                // Fetch the submitter's details using the doctor_id
                $submitter = User::select('id', 'name', 'lname', 'isSyndicateCardRequired')
                    ->where('id', $outcomeSubmitterDoctorId)
                    ->first(); // Use first() instead of get() to retrieve a single record

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
                    ],
                    'submitter' => [
                        'submitter_id' => optional($submitter)->id,
                        'submitter_fname' => (optional($submitter)->name) ? optional($submitter)->name : null,
                        'submitter_lname' => (optional($submitter)->lname) ? optional($submitter)->lname : null,
                        'submitter_SyndicateCard' => optional($submitter)->isSyndicateCardRequired
                    ]
                ];
            };

            $currentPatientsResponseData = $currentPatients->map($transformPatientData);
            $allPatientsResponseData = $allPatients->map($transformPatientData);

            // Get patient count and score value
            $userPatientCount = $user->patients()->count();
            $allPatientCount = Patients::count();
            $scoreValue = optional($user->score)->score ?? 0;
            $isVerified = (bool)$user->email_verified_at;

            // Get unread notification count
            $unreadCount = AppNotification::where('doctor_id', $user->id)
                ->where('read', false)->count();

            // Get SyndicateCard value
            $isSyndicateCardRequired = $user->isSyndicateCardRequired;

            $isUserBlocked = $user->blocked;

            // Get the first role
            $role = $user->roles->first();

            $update_message = '<ul><li><strong>Doctor Consultations</strong>: Doctors can now consult one or more colleagues for advice on their patients.</li><li><strong>User Achievements</strong>: Earn achievements by adding a set number of patients or completing specific outcomes.</li></ul>';

            // Prepare response data
            $response = [
                'value' => true,
                'app_update_message' => $update_message,
                'verified' => $isVerified,
                'unreadCount' => (string)$unreadCount,
                'doctor_patient_count' => (string)$userPatientCount,
                'isSyndicateCardRequired' => $isSyndicateCardRequired,
                'isUserBlocked' => $isUserBlocked,
                'all_patient_count' => (string)$allPatientCount,
                'score_value' => (string)$scoreValue,
                'role' => $role->name ?? "User",
                'data' => [
                    'topDoctors' => $topDoctors,
                    'pendingSyndicateCard' => $pendingSyndicateCard,
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
            // Retrieve the authenticated user
            $user = Auth::user();
            // Check if the user is an Admin or Tester
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Return all patients
            $allPatients = Patients::select('id', 'doctor_id', 'updated_at')
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false); // Non-admin/tester users only see non-hidden patients
                })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
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
            // Retrieve the authenticated user
            $user = Auth::user();
            // Check if the user is an Admin or Tester
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Return current patients for the user
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false); // Non-admin/tester users only see non-hidden patients
                })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
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

    public function doctorProfileGetPatients()
    {
        try {
            // Retrieve the authenticated user
            $user = Auth::user();
            // Check if the user is an Admin or Tester
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Return current patients for the user
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false); // Non-admin/tester users only see non-hidden patients
                })
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
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


    /**
     * Store a newly created resource in storage.
     */
    public function storePatient(Request $request)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            $doctor_id = Auth::id();

            // Retrieve the authenticated user
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Retrieve question IDs and their corresponding section IDs from the database
            $questionSectionIds = Questions::pluck('section_id', 'id')->toArray();

            if ($isAdminOrTester){
                $hidden = true;
            }else{
                $hidden = false;
            }

            // Create a new patient record
            $patient = Patients::create([
                'doctor_id' => $doctor_id,
                'hidden' => $hidden,
            ]);

            // Initialize arrays to store data for batch operations
            $answersToSave = [];
            $patientStatusesToCreate = [];

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

                        // Prepare data for batch insert of answers
                        $this->prepareAnswersToSave($answersToSave, $doctor_id, $questionId, $answers, $patient->id, false, $sectionId);
                        $this->prepareAnswersToSave($answersToSave, $doctor_id, $questionId, $otherFieldAnswer, $patient->id, true, $sectionId);
                    } elseif (isset($questionSectionIds[$questionId])) {
                        // Prepare data for batch insert of answers
                        $this->prepareAnswersToSave($answersToSave, $doctor_id, $questionId, $value, $patient->id, false, $sectionId);
                    }
                }
            }

            // Batch insert answers
            Answers::insert($answersToSave);

            // Create patient status records
            $patientStatusesToCreate[] = [
                'doctor_id' => $doctor_id,
                'patient_id' => $patient->id,
                'key' => 'section_' . ($questionSectionIds[1] ?? null),
                'status' => true
            ];

            $patientStatusesToCreate[] = [
                'doctor_id' => $doctor_id,
                'patient_id' => $patient->id,
                'key' => 'submit_status',
                'status' => false
            ];

            PatientStatus::insert($patientStatusesToCreate);

            // Logging successful patient creation
            Log::info('New patient created', ['doctor_id' => $doctor_id, 'patient_id' => $patient->id]);

            // Notifying other doctors (assuming this is optimized elsewhere)

            // Retrieve patient name using the updatedAnswersToSave array
            $patientName = $this->retrievePatientName($answersToSave, $patient->id);

            // Commit the transaction
            DB::commit();

            //test
            $response = [
                'value' => true,
                'doctor_id' => $doctor_id,
                'id' => $patient->id,
                'name' => $patientName, // Remove additional quotes
                'submit_status' => false,
                'message' => 'Patient Created Successfully',
            ];

            // Retrieve all doctors with role 'admin' or 'tester' except the authenticated user
            $doctors = User::role(['Admin', 'Tester'])
                ->where('id', '!=', Auth::id())
                ->pluck('id'); // Get only the IDs of the users

            // Create a new patient notification
            foreach ($doctors as $doctorId) {
                AppNotification::create([
                    'doctor_id' => $doctorId,
                    'type' => 'New Patient',
                    'content' => 'New Patient was created',
                    'patient_id' => $patient->id
                ]);
            }

            $title = 'New Patient was created 📣';
            $body = 'Dr. ' . ucfirst($user->name) . ' added a new patient named ' . $patientName;
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();

            $this->notificationController->sendPushNotification($title,$body,$tokens);

            $this->achievement->checkAndAssignAchievements($user);

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
     * Prepare data for batch insert of answers.
     *
     * @param array $answersToSave
     * @param int $doctor_id
     * @param int $questionId
     * @param mixed $answer
     * @param int $patientId
     * @param bool $isOtherField
     * @param int|null $sectionId
     * @return void
     */
    private function prepareAnswersToSave(&$answersToSave, $doctor_id, $questionId, $answer, $patientId, $isOtherField, $sectionId)
    {
        // Ensure the answer is wrapped in double quotes for storage
        $answerText = is_array($answer) ? json_encode($answer) : '"' . addslashes($answer) . '"';

        // Append data for batch insert of answers
        $answersToSave[] = [
            'doctor_id' => $doctor_id,
            'section_id' => $sectionId, // Pass section ID
            'question_id' => $questionId,
            'patient_id' => $patientId,
            'answer' => $answerText, // Ensure answer is wrapped in double quotes
            'type' => $isOtherField ? 'other' : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Retrieve patient name from the saved answers.
     *
     * @param array $answersToSave
     * @param int $patientId
     * @return string|null
     */
    private function retrievePatientName($answersToSave, $patientId)
    {
        foreach ($answersToSave as $answer) {
            if ($answer['patient_id'] === $patientId && $answer['question_id'] === 1) {
                // Remove extra quotes from the name
                return stripslashes(trim($answer['answer'], '"'));
            }
        }
        return null;
    }


    /**
     * Update the specified resource in storage.
     */
    public function updatePatient(UpdatePatientsRequest $request, $section_id, $patient_id)
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

                        $questionIsExists = Answers::where('patient_id', $patient_id)
                            ->where('question_id', $questionId)
                            ->first();

                        if ($questionIsExists) {
                            // Handle file upload if question type is files
                            if ($this->isFileTypeQuestion($questionId)) {
                                $fileUrls = $this->handleFileUploads($value);
                                $this->updateAnswer($questionId, json_encode($fileUrls), $patient_id, false, $section_id);
                            } else {
                                // Check if the question has already been processed
                                if (isset($value['answers']) && is_array($value['answers'])) {
                                    // Process the answer for the question
                                    $answers = $value['answers'];
                                    $otherFieldAnswer = $value['other_field'] ?? null;

                                    $this->updateAnswer($questionId, json_encode($answers), $patient_id, false, $section_id);
                                    $this->updateAnswer($questionId, json_encode($otherFieldAnswer), $patient_id, true, $section_id);
                                } elseif (isset($questionSectionIds[$questionId])) {
                                    // Save the answer along with the corresponding section ID
                                    $this->updateAnswer($questionId, json_encode($value), $patient_id, false, $section_id);
                                }
                            }
                        } else {
                            // Handle file upload if question type is files
                            if ($this->isFileTypeQuestion($questionId)) {
                                $fileUrls = $this->handleFileUploads($value);
                                $this->saveAnswer($doctor_id, $questionId, json_encode($fileUrls), $patient_id, false, $section_id);
                            } else {
                                // Check if the question has already been processed
                                if (isset($value['answers']) && is_array($value['answers'])) {
                                    // Process the answer for the question
                                    $answers = $value['answers'];
                                    $otherFieldAnswer = $value['other_field'] ?? null;

                                    // Save the answers and other field answer
//                                    $this->saveAnswer($doctor_id, $questionId, json_encode($answers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $patient_id, false, $section_id);
                                    $this->saveAnswer($doctor_id, $questionId, $answers, $patient_id, false, $section_id);
                                    $this->saveAnswer($doctor_id, $questionId, $otherFieldAnswer, $patient_id, true, $section_id);
                                } elseif (isset($questionSectionIds[$questionId])) {
                                    // Save the answer along with the corresponding section ID
                                    $this->saveAnswer($doctor_id, $questionId, $value, $patient_id, false, $section_id);
                                }
                            }
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
                        $questionIsExists = Answers::where('patient_id', $patient_id)
                            ->where('question_id', $questionId)
                            ->first();
                        if ($questionIsExists) {
                            // Handle file upload if question type is files
                            if ($this->isFileTypeQuestion($questionId)) {
                                $fileUrls = $this->handleFileUploads($value);
                                $this->updateAnswer($questionId, json_encode($fileUrls), $patient_id, false, $section_id);
                            } else {
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
                        } else {
                            // Handle file upload if question type is files
                            if ($this->isFileTypeQuestion($questionId)) {
                                $fileUrls = $this->handleFileUploads($value);
                                $this->saveAnswer($doctor_id, $questionId, json_encode($fileUrls), $patient_id, false, $section_id);
                            } else {
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

            $patientOutcomeStatus = PatientStatus::where('patient_id', $patient_id)
                ->where('key', 'outcome_status')
                ->where('status', true)
                ->first();

            if (!$patientOutcomeStatus && $section_id == 8) {
                PatientStatus::create([
                    'doctor_id' => $doctor_id,
                    'patient_id' => $patient_id,
                    'key' => 'outcome_status',
                    'status' => true
                ]);

                // Scoring system
                $doctorId = Auth::id();
                $incrementAmount = 1;
                $action = 'Add Outcome';

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
                    'patient_id' => $patient_id,
                    'timestamp' => now(),
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

    protected function isFileTypeQuestion($questionId)
    {
        // Add logic to determine if the question is of file type based on question ID
        $question = Questions::find($questionId);
        return $question && $question->type === 'files';
    }

    protected function handleFileUploads($files)
    {
        $filePaths = [];

        foreach ($files as $file) {
            // Get file data and name from the request
            $fileData = $file['file_data'];
            $fileName = $file['file_name'];

            // Check if both file name and data are present
            if (!$fileData || !$fileName) {
                throw new \Exception('File name or data is missing');
            }

            try {
                // Decode base64 data
                $fileContent = base64_decode($fileData);

                // Define the path to save the file in the medical_reports folder
                $filePath = 'medical_reports/' . $fileName;

                // Save file to storage or public folder
                Storage::disk('public')->put($filePath, $fileContent);

                // Log successful upload
                \Log::info("File uploaded successfully: $fileName");

                // Add the file path to the response array
                $filePaths[] = $filePath;
            } catch (\Exception $e) {
                // Log upload failure
                \Log::error("Failed to upload file $fileName: " . $e->getMessage());
                // You might want to handle or rethrow the exception based on your application logic
                // throw new \Exception("Failed to upload file $fileName: " . $e->getMessage());
            }
        }

        return $filePaths;
    }

    protected function saveAnswer($doctor_id, $questionId, $answerText, $patientId, $isOtherField = false, $sectionId = null)
    {
        Patients::where('id', $patientId)
            ->update([
                'updated_at' => now(),
            ]);

        // Check if the question is of 'files' type
        $question = Questions::find($questionId);
        if ($question && $question->type === 'files') {
            // Encode file paths array into JSON format
            //$answerText = json_encode($answerText);
            $answerText = is_array($answerText) ? json_encode($answerText, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $answerText;
        }

        // Create a new answer record
        Answers::create([
            'doctor_id' => $doctor_id,
            'section_id' => $sectionId, // Pass section ID
            'question_id' => $questionId,
            'patient_id' => $patientId,
            'answer' => $answerText, // Convert array to JSON string if it's an array
            'type' => $isOtherField ? 'other' : null,
        ]);
    }


    protected function updateAnswer($questionId, $answerText, $patientId, $isOtherField = false, $sectionId = null)
    {
        // Check if the question is of 'files' type
        $question = Questions::find($questionId);
        if ($question && $question->type === 'files') {
            // Encode file paths array into JSON format
            $answerText = json_encode($answerText);
        }

        Patients::where('id', $patientId)
            ->update([
                'updated_at' => now(),
            ]);

        // Update the answer record based on whether it's for 'other' or normal type
        if ($isOtherField) {
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->whereNotNull('type')
                ->update([
                    'answer' => $answerText,
                    'type' => 'other',
                ]);
        } else {
            Answers::where('patient_id', $patientId)
                ->where('question_id', $questionId)
                ->whereNull('type')
                ->update([
                    'answer' => $answerText,
                    'type' => null,
                ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyPatient($id)
    {
        DB::beginTransaction(); // Start a transaction

        try {
            //$doctorId = Auth::id();

            // Find the patient
            $patient = Patients::findOrFail($id);

            // Log the patient details for debugging
            Log::info('Deleting patient', ['patient_id' => $id]);

            // Delete related consultation_doctors records
            ConsultationDoctor::whereIn('consultation_id', function($query) use ($id) {
                $query->select('id')
                    ->from('consultations')
                    ->where('patient_id', $id);
            })->delete();

            // Log the deletion of consultation_doctors
            Log::info('Deleted consultation_doctors records for patient', ['patient_id' => $id]);

            // Delete related consultations records
            Consultation::where('patient_id', $id)->delete();

            // Log the deletion of consultations
            Log::info('Deleted consultations records for patient', ['patient_id' => $id]);



            // Retrieve score histories related to the patient
            $scoreHistories = ScoreHistory::where('patient_id', $id)->get();

            // Group the histories by doctor and calculate the total points for each doctor
            $doctorDecrementAmounts = $scoreHistories->groupBy('doctor_id')->map(function ($histories) {
                return $histories->sum('score');
            });

            foreach ($doctorDecrementAmounts as $doctorId => $decrementAmount) {
                if ($decrementAmount > 0) {
                    // Find or create the score record for the doctor
                    $score = Score::firstOrNew(['doctor_id' => $doctorId]);

                    // Deduct the points from both the score and threshold
                    $score->score -= $decrementAmount;
                    $score->threshold -= $decrementAmount;

                    // Ensure the score and threshold are not negative
                    $score->score = max(0, $score->score);
                    $score->threshold = max(0, $score->threshold);

                    // Save the updated score
                    $score->save();
                }
            }

            // Delete the patient
            $patient->delete();

            // Log successful patient deletion
            Log::info('Patient deleted successfully', ['patient_id' => $id]);

            // Commit the transaction if everything is successful
            DB::commit();

            $response = [
                'value' => true,
                'message' => 'Patient and related data deleted successfully',
            ];

            return response($response, 200);

        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();

            // Log the error
            Log::error('Error deleting patient', [
                'patient_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $response = [
                'value' => false,
                'message' => 'Error occurred while deleting patient: ' . $e->getMessage(),
            ];

            return response($response, 500);
        }
    }

    public function searchNewold(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            // Retrieve doses
            $doses = Dose::select('id', 'title', 'description', 'dose', 'created_at')
                ->where('title', 'like', '%' . $doseQuery . '%')
                ->latest('updated_at')
                ->get();

            // Retrieve patients
            $patients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->where(function ($query) use ($patientQuery) {
                    $query->whereHas('doctor', function ($query) use ($patientQuery) {
                        $query->where('name', 'like', '%' . $patientQuery . '%');
                    })
                        ->orWhereHas('answers', function ($query) use ($patientQuery) {
                            $query->where('answer', 'like', '%' . $patientQuery . '%');
                        });
                })
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status:id,patient_id,key,status',
                    'answers:id,patient_id,answer,question_id'
                ])
                ->latest('updated_at')
                ->get();

            // Transform the patients data
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
            $perPage = 10;
            $slicedData = $transformedPatients->slice(($currentPage - 1) * $perPage, $perPage);
            $transformedPatientsPaginated = new LengthAwarePaginator(
                $slicedData->values(),
                count($transformedPatients),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');
                return response()->json([
                    'value' => true,
                    'data' => [
                        'patients' => [],
                        'doses' => [],
                    ],
                ], 200);
            } elseif (empty($patientQuery)) {
                Log::info('No patient search term provided.');
                $transformedPatientsPaginated = [];
            } elseif (empty($doseQuery)) {
                Log::info('No dose search term provided.');
                $doses = [];
            }


            // Log successful search
            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);
            return response()->json([
                'value' => true,
                'data' => [
                    'patients' => $transformedPatientsPaginated,
                    'doses' => $doses,
                ],
            ], 200);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function searchNew(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            // Retrieve doses
            $doses = Dose::select('id', 'title', 'description', 'dose', 'created_at')
                ->where('title', 'like', '%' . $doseQuery . '%')
                ->latest('updated_at')
                ->get();

            // Retrieve patients
            $patients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->where(function ($query) use ($patientQuery) {
                    $query->whereHas('doctor', function ($query) use ($patientQuery) {
                        $query->where('name', 'like', '%' . $patientQuery . '%');
                    })
                        ->orWhereHas('answers', function ($query) use ($patientQuery) {
                            $query->where('answer', 'like', '%' . $patientQuery . '%');
                        });
                })
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status:id,patient_id,key,status',
                    'answers:id,patient_id,answer,question_id'
                ])
                ->latest('updated_at')
                ->get();

            // Transform the patients data
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

            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');
                return response()->json([
                    'value' => true,
                    'data' => [
                        'patients' => [],
                        'doses' => [],
                    ],
                ], 200);
            } elseif (empty($patientQuery)) {
                Log::info('No patient search term provided.');
                $transformedPatients = collect(); // Return empty collection for patients
            } elseif (empty($doseQuery)) {
                Log::info('No dose search term provided.');
                $doses = collect(); // Return empty collection for doses
            }

            // Log successful search
            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);
            return response()->json([
                'value' => true,
                'data' => [
                    'patients' => $transformedPatients,
                    'doses' => $doses,
                ],
            ], 200);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function realTimeSearch(Request $request)
    {
        $data = [
            'patients' => [],
            'doses' => [],
        ];

        try {
            // Validate the incoming request data
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            // Retrieve doses
            $doses = Dose::select('id', 'title', 'description', 'dose', 'created_at')
                ->where('title', 'like', '%' . $doseQuery . '%')
                ->latest('updated_at')
                ->get();

            // Retrieve patients
            $patients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->where(function ($query) use ($patientQuery) {
                    $query->whereHas('doctor', function ($query) use ($patientQuery) {
                        $query->where('name', 'like', '%' . $patientQuery . '%');
                    })
                        ->orWhereHas('answers', function ($query) use ($patientQuery) {
                            $query->where('answer', 'like', '%' . $patientQuery . '%');
                        });
                })
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status:id,patient_id,key,status',
                    'answers:id,patient_id,answer,question_id'
                ])
                ->latest('updated_at')
                ->get();

            // Transform the patients data
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

            // Update data variable with retrieved results
            $data['patients'] = $transformedPatients;
            $data['doses'] = $doses;

            // Return JSON response for AJAX
            return response()->json([
                'value' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

    public function realTimeSearchold(Request $request)
    {
        try {
            // Validate the incoming request data
            $request->validate([
                'dose' => 'nullable|string|max:255',
                'patient' => 'nullable|string|max:255',
            ]);

            $doseQuery = $request->input('dose', '');
            $patientQuery = $request->input('patient', '');

            // Retrieve doses
            $doses = Dose::select('id', 'title', 'description', 'dose', 'created_at')
                ->where('title', 'like', '%' . $doseQuery . '%')
                ->latest('updated_at')
                ->get();

// Retrieve patients
            $patients = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->where(function ($query) use ($patientQuery) {
                    $query->whereHas('doctor', function ($query) use ($patientQuery) {
                        $query->where('name', 'like', '%' . $patientQuery . '%');
                    })
                        ->orWhereHas('answers', function ($query) use ($patientQuery) {
                            $query->where('answer', 'like', '%' . $patientQuery . '%');
                        });
                })
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status:id,patient_id,key,status,doctor_id', // Add doctor_id in status
                    'answers:id,patient_id,answer,question_id'
                ])
                ->latest('updated_at')
                ->get();

            // Transform the patients data
            $transformedPatients = $patients->map(function ($patient) {
                $submitStatus = optional($patient->status->where('key', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'outcome_status')->first())->status;

                $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
                $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

                // Get doctor_id of the submitter from outcome status
                $outcomeSubmitterDoctorId = optional($patient->status->where('key', 'outcome_status')->first())->doctor_id;

                // Fetch the submitter's details using the doctor_id
                $submitter = User::select('id', 'name', 'lname', 'isSyndicateCardRequired')
                    ->where('id', $outcomeSubmitterDoctorId)
                    ->first(); // Use first() instead of get() to retrieve a single record

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
                        'submitter_id' => optional($submitter)->id,
                        'submitter_name' => (optional($submitter)->name && optional($submitter)->lname)
                            ? optional($submitter)->name . ' ' . optional($submitter)->lname
                            : null,
                        'submitter_SyndicateCard' => optional($submitter)->isSyndicateCardRequired
                    ]
                ];
            });


            if (empty($patientQuery) && empty($doseQuery)) {
                Log::info('No search term provided.');
                return response()->json([
                    'value' => true,
                    'data' => [
                        'patients' => [],
                        'doses' => [],
                    ],
                ], 200);
            } elseif (empty($patientQuery)) {
                Log::info('No patient search term provided.');
                $transformedPatients = collect(); // Return empty collection for patients
            } elseif (empty($doseQuery)) {
                Log::info('No dose search term provided.');
                $doses = collect(); // Return empty collection for doses
            }

            // After retrieving search results
            broadcast(new SearchResultsUpdated([
                'patients' => $transformedPatients,
                'doses' => $doses,
            ]));

            // Log successful search
            Log::info('Successfully retrieved data for the search term.', ['search_term' => $patientQuery]);
//            return response()->json([
//                'value' => true,
//                'data' => [
//                    'patients' => $transformedPatients,
//                    'doses' => $doses,
//                ],
//            ], 200);

            // After searching, return the view with the data
            return view('search', [
                'data' => [
                    'patients' => $transformedPatients,
                    'doses' => $doses,
                ],
            ]);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
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
            $pdf = PDF::loadView('patient_pdf2', $pdfData);

            // Ensure the 'pdfs' directory exists in the public disk
            Storage::disk('public')->makeDirectory('pdfs');

            $patientName = $patient->answers->where('question_id', '1')->first();

            // Generate a unique filename for the PDF
            $pdfFileName = $patientName->answer .'_'. date("dmy_His"). '.pdf';

            // Save the PDF file to the public disk
            Storage::disk('public')->put('pdfs/' . $pdfFileName, $pdf->output());

            // Generate the URL for downloading the PDF file
            $pdfUrl = config('app.url') . '/' . 'storage/pdfs/' . $pdfFileName;

            //\Log::info('Patient Answers:', ['answers' => $patient->answers]);

            // Return the URL to download the PDF file along with patient data
            return response()->json([
                'pdf_url' => $pdfUrl,
               // 'data' => $pdfData
            ]);

            // Pass the data to the blade view
//            $viewData = [
//                'patient' => $patient,
//                'questionData' => $data
//                // Add more data here if needed
//            ];
//            // Return the view with the data
//            return view('patient_pdf2', $viewData);
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
            ->where('id', 132)
            ->with(['doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'image','syndicate_card','isSyndicateCardRequired', 'version');
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
