<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\Consultation;
use App\Models\ConsultationDoctor;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Patients\Models\Patients;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    protected $notificationService;

    protected $patients;

    public function __construct(NotificationService $notificationService, Patients $patients)
    {
        $this->notificationService = $notificationService;
        $this->patients = $patients;
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consult_message' => 'required|string',
            'consult_doctor_ids' => 'required|array',
            'consult_doctor_ids.*' => 'exists:users,id',
        ]);

        $consultation = Consultation::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'consult_message' => $request->consult_message,
            'status' => 'pending',
        ]);

        $doctors = $request->consult_doctor_ids;

        foreach ($doctors as $consult_doctor_id) {
            ConsultationDoctor::create([
                'consultation_id' => $consultation->id,
                'consult_doctor_id' => $consult_doctor_id,
                'status' => 'not replied',
            ]);
        }

        $response = [
            'value' => true,
            'data' => $consultation,
            'message' => 'Consultation Created Successfully',
        ];

        $user = Auth::user();

        // Batch insert notifications to avoid N+1 queries
        $notifications = collect($doctors)->map(function ($doctorId) use ($user, $consultation, $request) {
            return [
                'doctor_id' => $doctorId,
                'type' => 'Consultation',
                'type_id' => $consultation->id,
                'content' => 'Dr. '.$user->name.' is seeking your advice for his patient',
                'type_doctor_id' => Auth::id(),
                'patient_id' => $request->patient_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        AppNotification::insert($notifications);

        $title = 'New consultation request was created 📣';
        $body = 'Dr. '.$user->name.' is seeking your advice for his patient';
        $tokens = FcmToken::whereIn('doctor_id', $doctors)
            ->pluck('token')
            ->toArray();

        $this->notificationService->sendPushNotification($title, $body, $tokens);

        return response($response, 201);
    }

    public function sentRequests()
    {
        // Fetch consultations with associated doctor and patient data, ordered by updated_at in descending order
        $consultations = Consultation::where('doctor_id', Auth::id())
            ->with('doctor')
            ->with('patient')
            ->orderBy('updated_at', 'desc') // Order by updated_at in descending order
            ->get();

        // Pre-fetch all patient names to avoid N+1 queries
        $patientIds = $consultations->pluck('patient_id')->unique()->toArray();
        $patientNames = Answers::whereIn('patient_id', $patientIds)
            ->where('question_id', '1')
            ->pluck('answer', 'patient_id');

        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($consultations as $consultation) {
            // Get patient name from pre-fetched data
            $patientName = $patientNames->get($consultation->patient_id);

            // Prepare the consultation object with required details
            $consultationData = [
                'id' => strval($consultation->id),
                'consult_message' => $consultation->consult_message,
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isSyndicateCard' => $consultation->doctor->isSyndicateCardRequired,
                'patient_id' => strval($consultation->patient_id),
                'patient_name' => $patientName,
                'status' => $consultation->status,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
            ];

            // Add the consultation object to the response array
            $response[] = $consultationData;
        }

        // Return the response as JSON
        return response()->json($response);
    }

    public function receivedRequests()
    {
        //$consultations = ConsultationDoctor::where('consult_doctor_id', Auth::id())->with('consultation')->get();
        // Fetch consultations with associated doctor and patient data
        $ConsultationDoctor = ConsultationDoctor::where('consult_doctor_id', Auth::id())
            ->with('consultation')
            ->with('consultDoctor')
            ->with('consultation.doctor')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Pre-fetch all patient names to avoid N+1 queries
        $patientIds = $ConsultationDoctor->pluck('consultation.patient_id')->unique()->toArray();
        $patientNames = Answers::whereIn('patient_id', $patientIds)
            ->where('question_id', '1')
            ->pluck('answer', 'patient_id');

        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($ConsultationDoctor as $ConsultationDoctor) {
            // Get patient name from pre-fetched data
            $patientName = $patientNames->get($ConsultationDoctor->consultation->patient_id);

            // Prepare the consultation object with required details
            $consultationData = [
                'id' => strval($ConsultationDoctor->consultation->id),
                'consult_message' => $ConsultationDoctor->consultation->consult_message,
                'doctor_id' => strval($ConsultationDoctor->consultation->doctor->id),
                'doctor_fname' => $ConsultationDoctor->consultation->doctor->name,
                'doctor_lname' => $ConsultationDoctor->consultation->doctor->lname,
                'workingplace' => $ConsultationDoctor->consultation->doctor->workingplace,
                'image' => $ConsultationDoctor->consultation->doctor->image,
                //                'isSyndicateCard' => $ConsultationDoctor->consultDoctor->isSyndicateCardRequired === 'Verified' ? 'true' : 'false',
                'isSyndicateCard' => $ConsultationDoctor->consultation->doctor->isSyndicateCardRequired,
                'patient_id' => strval($ConsultationDoctor->consultation->patient_id),
                'patient_name' => $patientName,
                'status' => $ConsultationDoctor->consultation->status,
                'created_at' => $ConsultationDoctor->consultation->created_at,
                'updated_at' => $ConsultationDoctor->consultation->updated_at,
            ];

            // Add the consultation object to the response array
            $response[] = $consultationData;
        }

        //        // Sort the response array by updated_at in descending order (in case you want additional sorting)
        //        usort($response, function ($a, $b) {
        //            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        //        });

        // Return the response as JSON
        return response()->json($response);
    }

    public function consultationDetails($id)
    {
        // Fetch consultations with proper eager loading to avoid N+1 queries
        $consultations = Consultation::where('id', $id)
            ->with([
                'consultationDoctors',
                'doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'workingplace', 'image', 'isSyndicateCardRequired');
                },
                'patient' => function ($query) {
                    $query->select('id', 'doctor_id', 'updated_at')
                        ->with([
                            'doctor' => function ($q) {
                                $q->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
                            },
                            'status' => function ($q) {
                                $q->select('id', 'patient_id', 'key', 'status')
                                    ->whereIn('key', ['submit_status', 'outcome_status']);
                            },
                            'answers' => function ($q) {
                                $q->select('id', 'patient_id', 'answer', 'question_id')
                                    ->whereIn('question_id', [1, 2]); // Only fetch needed answers
                            },
                        ]);
                },
            ])
            ->whereHas('consultationDoctors', function ($query) {
                // Only include Consultations where the authenticated user has a record
                //$query->where('consult_doctor_id', Auth::id());
            })
            ->get();

        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($consultations as $consultation) {
            // Access already loaded relationships - no additional queries
            $patient = $consultation->patient;

            // Get patient name from already loaded answers
            $patientName = optional($patient->answers->where('question_id', 1)->first())->answer;

            // Transform the patient data using already loaded relationships
            $transformedPatient = null;

            if ($patient) {
                // Create indexed collections for O(1) lookups instead of O(n) where() calls
                $statusByKey = $patient->status->keyBy('key');
                $answersByQuestionId = $patient->answers->keyBy('question_id');

                $submitStatus = optional($statusByKey->get('submit_status'))->status;
                $outcomeStatus = optional($statusByKey->get('outcome_status'))->status;

                $nameAnswer = optional($answersByQuestionId->get(1))->answer;
                $hospitalAnswer = optional($answersByQuestionId->get(2))->answer;

                $transformedPatient = [
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
                    ],
                ];
            }

            // Prepare the consultation object with required details including consultationDoctors
            $consultationData = [
                'id' => strval($consultation->id),
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isVerified' => $consultation->doctor->isSyndicateCardRequired === 'Verified',
                'status' => $consultation->status,
                'consult_message' => $consultation->consult_message,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
                'patient_info' => $transformedPatient,
                'consultationDoctors' => $consultation->consultationDoctors->map(function ($consultationDoctor) {
                    return [
                        'id' => strval($consultationDoctor->id),
                        'consultation_id' => strval($consultationDoctor->consultation_id),
                        'consult_doctor_id' => strval($consultationDoctor->consult_doctor_id),
                        'consult_doctor_fname' => $consultationDoctor->consultDoctor->name,
                        'consult_doctor_lname' => $consultationDoctor->consultDoctor->lname,
                        'consult_doctor_image' => $consultationDoctor->consultDoctor->image,
                        'workingplace' => $consultationDoctor->consultDoctor->workingplace,
                        'isVerified' => $consultationDoctor->consultDoctor->isSyndicateCardRequired === 'Verified',
                        'reply' => $consultationDoctor->reply ?? 'No reply available',
                        'status' => $consultationDoctor->status,
                        'created_at' => $consultationDoctor->created_at,
                        'updated_at' => $consultationDoctor->updated_at,
                    ];
                }),
            ];

            // Add the consultation object to the response array
            $response = $consultationData;
        }

        // Return the response as JSON
        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        try {
            // Retrieve the authenticated user
            $user = Auth::user();

            // Attempt to find the ConsultationDoctor record for the given consultation and doctor
            $consultationDoctor = ConsultationDoctor::where('consultation_id', $id)
                ->where('consult_doctor_id', $user->id)
                ->firstOrFail();

            // Update the reply and status fields
            $consultationDoctor->reply = $request->input('reply');
            $consultationDoctor->status = 'replied';
            $consultationDoctor->save();

            // Check if all doctors involved in the consultation have replied
            $allReplied = ConsultationDoctor::where('consultation_id', $id)
                ->where('status', '!=', 'replied')
                ->count() === 0;

            // If all doctors have replied, mark the consultation as complete
            if ($allReplied) {
                $consultation = $consultationDoctor->consultation;
                $consultation->status = 'complete';
                $consultation->save();
            }

            // Prepare notification details
            $doctorId = Consultation::where('id', $id)
                ->value('doctor_id'); // Fetch only the doctor_id

            // Create a new notification for the doctor who created the consultation request
            AppNotification::create([
                'doctor_id' => $doctorId,
                'type' => 'Consultation',
                'type_id' => $id,
                'content' => 'Dr. '.$user->name.' has replied to your consultation request. 📩',
                'type_doctor_id' => $user->id,
                'patient_id' => $request->patient_id,
            ]);

            // Prepare and send push notifications to relevant doctors
            $title = 'New Reply on Consultation Request 🔔';
            $body = 'Dr. '.$user->name.' has replied to your consultation request. 📩';
            $tokens = FcmToken::whereIn('doctor_id', [$doctorId]) // Wrap $doctorId in an array
                ->pluck('token')
                ->toArray();

            // Send push notifications
            $this->notificationService->sendPushNotification($title, $body, $tokens);

            // Return success response with detailed log
            Log::info('Consultation request updated successfully.', [
                'consultation_id' => $id,
                'doctor_id' => $user->id,
                'reply' => $request->input('reply'),
                'all_replied' => $allReplied,
                'notification_tokens' => $tokens,
                'notification_body' => $body,
            ]);

            return response()->json([
                'message' => 'Consultation request updated successfully',
                'data' => [
                    'consultation_id' => $id,
                    'doctor_id' => $user->id,
                    'reply' => $request->input('reply'),
                    'all_replied' => $allReplied,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            // Handle the case where the consultation doctor record was not found
            Log::warning('Consultation doctor not found.', [
                'consultation_id' => $id,
                'doctor_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Consultation doctor not found for the provided consultation ID.',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions that might occur
            Log::error('An error occurred while updating the consultation request.', [
                'consultation_id' => $id,
                'doctor_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while updating the consultation request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function consultationSearch($data)
    {
        try {
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Explode the input string into words
            $keywords = explode(' ', $data);

            $users = User::select('id', 'name', 'lname', 'email', 'phone', 'specialty', 'workingplace', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->when(! $isAdminOrTester, function ($query) {
                    return $query->where('id', '!=', Auth::id());
                })
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $word) {
                        $query->where(function ($subQuery) use ($word) {
                            $subQuery->where('name', 'like', '%'.$word.'%')
                                ->orWhere('lname', 'like', '%'.$word.'%')
                                ->orWhere('email', 'like', '%'.$word.'%')
                                ->orWhere('phone', 'like', '%'.$word.'%');
                        });
                    }
                })
                ->withCount('patients')
                ->selectSub(function ($query) {
                    $query->selectRaw('COALESCE(score, 0)')
                        ->from('scores')
                        ->whereColumn('users.id', 'scores.doctor_id')
                        ->limit(1);
                }, 'score')
                ->orderByRaw('COALESCE(score, 0) DESC, patients_count DESC')
                ->get()
                ->map(function ($user) {
                    $user->patients_count = strval($user->patients_count);

                    return $user;
                });

            return response()->json([
                'value' => true,
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }
}
