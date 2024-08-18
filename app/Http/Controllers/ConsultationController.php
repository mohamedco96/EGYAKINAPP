<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\Patients;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Consultation;
use App\Models\ConsultationDoctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ConsultationController extends Controller
{
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

        foreach ($request->consult_doctor_ids as $consult_doctor_id) {
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

        return response($response, 201);
    }

    public function sentRequests()
    {
        // Fetch consultations with associated doctor and patient data
        $consultations = Consultation::where('doctor_id', Auth::id())
            ->with('doctor')
            ->with('patient')
            ->get();

        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($consultations as $consultation) {
            // Get patient ID and fetch the patient's name
            $patientId = $consultation->patient_id;
            $patientName = Answers::where('patient_id', $patientId)
                ->where('question_id', '1')
                ->pluck('answer')
                ->first();

            // Prepare the consultation object with required details
            $consultationData = [
                'id' => strval($consultation->id),
                'consult_message' => $consultation->consult_message,
                'doctor_id' => strval($consultation->doctor_id),
                'doctor_fname' => $consultation->doctor->name,
                'doctor_lname' => $consultation->doctor->lname,
                'workingplace' => $consultation->doctor->workingplace,
                'image' => $consultation->doctor->image,
                'isSyndicateCard' => $consultation->doctor->isSyndicateCardRequired === 'Verified' ? 'true' : 'false',
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
            ->get();

        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($ConsultationDoctor as $ConsultationDoctor) {
            // Get patient ID and fetch the patient's name
            $patientId = $ConsultationDoctor->consultation->patient_id;
            $patientName = Answers::where('patient_id', $patientId)
                ->where('question_id', '1')
                ->pluck('answer')
                ->first();

            // Prepare the consultation object with required details
            $consultationData = [
                'id' => strval($ConsultationDoctor->consultation->id),
                'consult_message' => $ConsultationDoctor->consultation->consult_message,
                'doctor_id' => strval($ConsultationDoctor->consultDoctor->id),
                'doctor_fname' => $ConsultationDoctor->consultDoctor->name,
                'doctor_lname' => $ConsultationDoctor->consultDoctor->lname,
                'workingplace' => $ConsultationDoctor->consultDoctor->workingplace,
                'image' => $ConsultationDoctor->consultDoctor->image,
                'isSyndicateCard' => $ConsultationDoctor->consultDoctor->isSyndicateCardRequired === 'Verified' ? 'true' : 'false',
                'patient_id' => strval($ConsultationDoctor->consultation->patient_id),
                'patient_name' => $patientName,
                'status' => $ConsultationDoctor->consultation->status,
                'created_at' => $ConsultationDoctor->consultation->created_at,
                'updated_at' => $ConsultationDoctor->consultation->updated_at,
            ];

            // Add the consultation object to the response array
            $response[] = $consultationData;
        }

        // Return the response as JSON
        return response()->json($response);
    }

    public function consultationDetails($id)
    {
        // Fetch consultations with associated doctor, patient, and consultationDoctors data
        $consultations = Consultation::where('id', $id)
            ->with(['consultationDoctors' => function ($query) {
                // Retrieve all consultationDoctors for each Consultation
            }])
            ->whereHas('consultationDoctors', function ($query) {
                // Only include Consultations where the authenticated user has a record
                $query->where('consult_doctor_id', Auth::id());
            })
            ->with('doctor')
            ->with('patient')
            ->get();




        // Initialize an array to hold the final response
        $response = [];

        // Iterate through each consultation to extract the required details
        foreach ($consultations as $consultation) {
            // Get patient ID and fetch the patient's name
            $patientId = $consultation->patient_id;
            $patientName = Answers::where('patient_id', $patientId)
                ->where('question_id', '1')
                ->pluck('answer')
                ->first();

            $patient = Patients::select('id', 'doctor_id', 'updated_at')
                ->where('id', $consultation->patient_id)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
                }])
                ->with(['status' => function ($query) {
                    $query->select('id', 'patient_id', 'key', 'status');
                }])
                ->with(['answers' => function ($query) {
                    $query->select('id', 'patient_id', 'answer', 'question_id');
                }])
                ->latest('updated_at')
                ->first(); // Use first() to get a single object

// Transform the single patient
            $transformedPatient = null;

            if ($patient) {
                $submitStatus = optional($patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                $nameAnswer = optional($patient->answers->where('question_id', 1)->first())->answer;
                $hospitalAnswer = optional($patient->answers->where('question_id', 2)->first())->answer;

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
                    ]
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
                'consultationDoctors' => $consultation->consultationDoctors->map(function($consultationDoctor) {
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
                })
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
            // Validate the incoming request
//            $request->validate([
//                'reply' => 'required|string',
//                'status' => 'required|string|in:accepted,rejected,replied',
//            ]);

            // Attempt to find the ConsultationDoctor record
            $consultationDoctor = ConsultationDoctor::where('consultation_id', $id)
                ->where('consult_doctor_id', Auth::id())
                ->firstOrFail();

            // Update the reply and status fields
            $consultationDoctor->reply = $request->reply;
            $consultationDoctor->status = 'replied';
            $consultationDoctor->save();

            // Check if all consultation doctors have replied
            $allReplied = ConsultationDoctor::where('consultation_id', $id)
                    ->where('status', '!=', 'replied')
                    ->count() === 0;

            if ($allReplied) {
                $consultationDoctor->consultation->status = 'complete';
                $consultationDoctor->consultation->save();
            }

            return response()->json(['message' => 'Consultation request updated successfully']);

        } catch (ModelNotFoundException $e) {
            // Handle the case where the consultation doctor was not found
            return response()->json([
                'message' => 'Consultation doctor not found for the provided consultation ID.',
            ], 404);
        } catch (\Exception $e) {
            // Handle any other exceptions that might occur
            return response()->json([
                'message' => 'An error occurred while updating the consultation request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function consultationSearch($data)
    {
        try {
            // Retrieve Users
            $users = User::select('id', 'name', 'lname', 'email', 'phone', 'specialty', 'workingplace', 'image', 'syndicate_card', 'isSyndicateCardRequired')
                ->where('name', 'like', '%' . $data . '%')
                ->orwhere('email', 'like', '%' . $data . '%')
                ->orwhere('phone', 'like', '%' . $data . '%')
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
                'data' => $users ], 200);

        } catch (\Exception $e) {
            // Log error
            Log::error('Error searching for data.', ['exception' => $e]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to search for data.',
            ], 500);
        }
    }

}
