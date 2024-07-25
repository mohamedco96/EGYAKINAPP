<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\WelcomeMailNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\PatientHistory;
use App\Models\Section;
use App\Models\Complaint;
use App\Models\Cause;
use App\Models\Risk;
use App\Models\Assessment;
use App\Models\Examination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create a new user
            $user = $this->createUser($request);

            // Generate an authentication token
            $token = $user->createToken('apptoken')->plainTextToken;

            // Send a welcome notification
            $user->notify(new WelcomeMailNotification());

            // Save FCM token if provided
            if ($request->has('fcmToken')) {
                $this->storeFcmToken($user->id, $request->fcmToken);
            }

            // Commit the transaction
            DB::commit();

            // Prepare the response
            $response = [
                'value' => true,
                'data' => $user,
                'token' => $token,
            ];

            return response($response, 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();
            Log::error("Error registering user: " . $e->getMessage());

            return response(['value' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a new user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\User
     */
    protected function createUser(Request $request)
    {
        return User::create([
            'name' => $request->input('name'),
            'lname' => $request->input('lname'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
            'age' => $request->input('age'),
            'specialty' => $request->input('specialty'),
            'workingplace' => $request->input('workingplace'),
            'phone' => $request->input('phone'),
            'job' => $request->input('job'),
            'highestdegree' => $request->input('highestdegree'),
            'registration_number' => $request->input('registration_number'),
        ]);
    }

    /**
     * Store the FCM token if it doesn't already exist.
     *
     * @param int $userId
     * @param string $fcmToken
     * @return void
     */
    protected function storeFcmToken(int $userId, string $fcmToken)
    {
        $existingToken = FcmToken::where('token', $fcmToken)->first();

        if (!$existingToken) {
            FcmToken::create([
                'doctor_id' => $userId,
                'token' => $fcmToken,
            ]);

            Log::info('FCM token stored successfully.', [
                'doctor_id' => $userId,
                'token' => $fcmToken,
            ]);
        }
    }

    public function registerbkp(Request $request)
    {
        $fields = $request->validate([
//            'name' => 'required|string',
//            'lname' => 'required|string',
//            //'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
//            'email' => 'required|string|unique:users,email',
//            'password' => 'required|string|confirmed',
//            'age' => 'integer',
//            'specialty' => 'required|string',
//            'workingplace' => 'required|string',
//            'phone' => 'required|string',
//            'job' => 'required|string',
//            'highestdegree' => 'required|string',
//            'registration_number' => 'required|string',
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'lname' => $fields['lname'],
            //'image' => $path,
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
            'age' => $fields['age'],
            'specialty' => $fields['specialty'],
            'workingplace' => $fields['workingplace'],
            'phone' => $fields['phone'],
            'job' => $fields['job'],
            'highestdegree' => $fields['highestdegree'],
            'registration_number' => $fields['registration_number'],
        ]);

        $token = $user->createToken('apptoken')->plainTextToken;
        $user->notify(new WelcomeMailNotification());
        $response = [
            'value' => true,
            'data' => $user,
            //'image' => $imageUrl,
            'token' => $token,
        ];

        if($request->has('fcmToken')){
            $existingToken = FcmToken::where('token', $request->fcmToken)->first();

            if (!$existingToken) {
                // Attempt to create a new FCM token
                FcmToken::create([
                    'doctor_id' => $user->id,
                    'token' => $request->fcmToken,
                ]);

                // Log the successful token storage
                Log::info('FCM token stored successfully.', [
                    'doctor_id' => $user->id,
                    'token' => $request->fcmToken,
                ]);
            }
        }


        return response($response, 200);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            $response = [
                'value' => false,
                'message' => 'wrong data',
            ];

            return response($response, 404);
        } elseif ($user->blocked) {
            $response = [
                'value' => false,
                'message' => 'User is Blocked',
            ];
            return response($response, 404);
        } else {
            $token = $user->createToken('apptoken')->plainTextToken;
            $response = [
                'value' => true,
                'data' => $user,
                'token' => $token,
            ];

            if($request->has('fcmToken')){
                $existingToken = FcmToken::where('token', $request->fcmToken)->first();

                if (!$existingToken) {
                    // Attempt to create a new FCM token
                    FcmToken::create([
                        'doctor_id' => $user->id,
                        'token' => $request->fcmToken,
                    ]);

                    // Log the successful token storage
                    Log::info('FCM token stored successfully.', [
                        'doctor_id' => $user->id,
                        'token' => $request->fcmToken,
                    ]);
                }
            }

            return response($response, 200);
        }
    }

    public function logout(Request $request)
    {
        //auth()->user()->tokens()->delete();
        $request->user()->currentAccessToken()->delete();
        return [
            'value' => true,
            'message' => 'Logged out',
        ];
    }

    /**
     * Change the authenticated user's password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'value' => false,
                'message' => 'Required data is missing or password field confirmation does not match'
                //'message' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'value' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'value' => true,
            'message' => 'Password changed successfully'
        ], 200);
    }

    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif', // max 2MB
            //'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            // Generate a unique file name using the original file name and a timestamp
            $fileName = time() . '_' . $image->getClientOriginalName();

            // Store the image in the specified directory with the generated file name
            $path = $image->storeAs('profile_images', $fileName, 'public');

            // Get the absolute URL of the uploaded image
            //$absolutePath = url(Storage::url($path));

            // Get the relative path of the uploaded image (without the storage folder prefix)
            $relativePath = 'storage/' . $path;

            // Update user's profile image path in the database
            auth()->user()->update(['image' => $path]);

            // Construct the full URL by appending the relative path to the APP_URL
            //$imageUrl = config('app.url') . '/' . 'storage/app/public/' . $path;
            $imageUrl = config('app.url') . '/' . 'storage/' . $path;

            return response()->json([
                'value' => true,
                'message' => 'Profile image uploaded successfully.',
                'image' => $imageUrl,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please choose an image file.',
        ], 400);
    }

    public function uploadSyndicateCard(Request $request)
    {
        $request->validate([
            'syndicate_card' => 'required|image|mimes:jpeg,png,jpg,gif', // max 2MB
            //'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
        ]);

        if ($request->hasFile('syndicate_card')) {
            $image = $request->file('syndicate_card');

            // Generate a unique file name using the original file name and a timestamp
            $fileName = time() . '_' . $image->getClientOriginalName();

            // Store the image in the specified directory with the generated file name
            $path = $image->storeAs('syndicate_card', $fileName, 'public');

            // Get the absolute URL of the uploaded image
            //$absolutePath = url(Storage::url($path));

            // Get the relative path of the uploaded image (without the storage folder prefix)
            $relativePath = 'storage/' . $path;

            // Update user's profile image path in the database
            auth()->user()->update(['syndicate_card' => $path, 'isSyndicateCardRequired' => 'Pending']);

            // Construct the full URL by appending the relative path to the APP_URL
            //$imageUrl = config('app.url') . '/' . 'storage/app/public/' . $path;
            $imageUrl = config('app.url') . '/' . 'storage/' . $path;

            return response()->json([
                'value' => true,
                'message' => 'User syndicate card uploaded successfully.',
                'image' => $imageUrl,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please choose an image file.',
        ], 400);
    }


    public function update(Request $request)
    {
        // Find the user by ID
        $doctor_id = Auth::id();
        $user = User::find($doctor_id);

        // Check if the user exists
        if ($user != null) {
            // Validate the request data
            $request->validate([
                'name' => 'string',
                'lname' => 'string',
                'syndicate_card' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // max 2MB
                'email' => 'string|email',
                'password' => 'string',
                'age' => 'integer',
                'specialty' => 'string',
                'workingplace' => 'string',
                'phone' => 'string',
                'job' => 'string',
                'highestdegree' => 'string',
                'registration_number' => 'string',
            ]);

            // Check if the email address is being updated
            if ($request->has('email') && $request->email !== $user->email) {
                // Update user's email and set email_verified_at to null
                $user->email = $request->email;
                $user->email_verified_at = null;
            }

            // Check if syndicate_card field is provided
            if ($request->hasFile('syndicate_card')) {
                // Get the image file
                $image = $request->file('syndicate_card');

                // Generate a unique file name using the original file name and a timestamp
                $fileName = time() . '_' . $image->getClientOriginalName();

                // Store the image in the specified directory with the generated file name
                $path = $image->storeAs('syndicate_cards', $fileName, 'public');

                // Update user's profile image path in the database
                $user->syndicate_card = $path;
            }

            // Update user's other fields except for email and syndicate_card
            $user->fill($request->except(['email', 'syndicate_card']));
            $user->save();

            // Construct the full URL by appending the relative path to the APP_URL
            $imageUrl = config('app.url') . '/' . 'storage/' . $user->syndicate_card;

            $response = [
                'value' => true,
                //'syndicate_card' => $imageUrl,
                'data' => $user,
                'message' => 'User Updated Successfully',
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No User was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::all();

        if ($user != null) {
            $response = [
                'value' => true,
                'data' => $user,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No user was found',
            ];

            return response($response, 404);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Find the user by ID with patients relation eager loaded
        $user = User::with('patients')->find($id);

        // Check if the user exists
        if ($user) {
            // Get the user's image URL
            //$imageUrl = $user->image ? url(Storage::url($user->image)) : null;
            $imageUrl = config('app.url') . '/' . 'storage/' . $user->image;
            // Get the number of patients associated with the user
            $patientCount = $user->patients()->count();

            // Get the user's score value
            $scoreValue = optional($user->score)->score ?? 0;

            // Prepare the response data
            $responseData = [
                'value' => true,
                'patient_count' => strval($patientCount) ?? 0,
                'score_value' => strval($scoreValue),
                'image' => $imageUrl,
                'data' => $user,
            ];

            // Return a success response
            return response()->json($responseData, 200);
        } else {
            // Return a not found response if the user does not exist
            $response = [
                'value' => false,
                'message' => 'No user was found',
            ];

            return response()->json($response, 404);
        }
    }


    public function showAnotherProfile($id)
    {
        // Find the user by ID with patients relation eager loaded
        $user = User::with('patients')->find($id);

        // Check if the user exists
        if ($user) {
            // Get the user's image URL
            //$imageUrl = $user->image ? url(Storage::url($user->image)) : null;
            $imageUrl = config('app.url') . '/' . 'storage/' . $user->image;
            // Get the number of patients associated with the user
            $patientCount = $user->patients()->count();

            // Get the user's score value
            $scoreValue = optional($user->score)->score ?? 0;

            // Prepare the response data
            $responseData = [
                'value' => true,
                'patient_count' => strval($patientCount) ?? 0,
                'score_value' => strval($scoreValue),
                'image' => $imageUrl,
                'data' => $user,
            ];

            // Return a success response
            return response()->json($responseData, 200);
        } else {
            // Return a not found response if the user does not exist
            $response = [
                'value' => false,
                'message' => 'No user was found',
            ];

            return response()->json($response, 404);
        }
    }

    public function doctorProfileGetPatients($id)
    {
        try {
            // Return all patients

            // Find the user by ID
            $user = User::find($id);

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'value' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->where('hidden', false)
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image','syndicate_card','isSyndicateCardRequired');
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

    public function doctorProfileGetScoreHistory($id)
    {
        try {
            // Return all patients

            // Find the user by ID
            $user = User::find($id);

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'value' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $getScoreHistory = $user->scoreHistory()
                ->select('id', 'doctor_id', 'score', 'action', 'updated_at')
                ->with(['doctor' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image','syndicate_card','isSyndicateCardRequired');
                }])
                ->latest('updated_at')
                ->get();

            // Transform the response
            $transformedPatients = $getScoreHistory->map(function ($score) {
                return [
                    'id' => $score->id,
                    'doctor_id' => $score->doctor_id,
                    'score' => $score->score,
                    'action' => $score->action,
                    'updated_at' => $score->updated_at,
                    'doctor' => $score->doctor,
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
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if ($user) {
            // Delete the user
            $user->delete();

            // Delete related records from other tables
            PatientHistory::where('doctor_id', $id)->delete();
            Section::where('doctor_id', $id)->delete();
            Complaint::where('doctor_id', $id)->delete();
            Cause::where('doctor_id', $id)->delete();
            Risk::where('doctor_id', $id)->delete();
            Assessment::where('doctor_id', $id)->delete();
            Examination::where('doctor_id', $id)->delete();

            // Prepare the success response
            $response = [
                'value' => true,
                'message' => 'User Deleted Successfully',
            ];

            // Return the success response
            return response()->json($response, 200);
        } else {
            // Prepare the error response if the user does not exist
            $response = [
                'value' => false,
                'message' => 'No User was found',
            ];

            // Return the error response
            return response()->json($response, 404);
        }
    }

}
