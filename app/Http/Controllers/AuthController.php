<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
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
use App\Http\Controllers\NotificationController;

class AuthController extends Controller
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }
    public function register(Request $request)
    {
        // Log the start of the user registration process
        Log::info('Starting user registration process.', ['request_data' => $request->all()]);

        // Start a database transaction to ensure data integrity
        DB::beginTransaction();

        try {
            // Create a new user from the request data
            $user = $this->createUser($request);

            // Generate an authentication token for the newly created user
            $token = $user->createToken('apptoken')->plainTextToken;

            // Send a welcome email notification to the user
            $user->notify(new WelcomeMailNotification());

            // If the request contains an FCM token, store it
            if ($request->has('fcmToken')) {
                $this->storeFcmToken($user->id, $request->fcmToken);
            }

            // Commit the transaction if everything went smoothly
            DB::commit();

            // Log the successful registration
            Log::info('User registration successful.', ['user_id' => $user->id]);

            // Prepare a successful response with user data and the generated token
            $response = [
                'value' => true,
                'data' => $user,
                'token' => $token,
            ];

            return response($response, 200);

        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollBack();

            // Log the error with specific details for easier debugging
            Log::error('Error during user registration.', [
                'error_message' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            // Return a concise error message with a 500 status code
            return response([
                'value' => false,
                'message' => 'Registration failed. Please try again later.'
            ], 500);
        }
    }

    /**
     * Create a new user with the provided request data.
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Models\User
     */
    protected function createUser(Request $request)
    {
        // Log the user creation process
        Log::info('Creating new user.', ['email' => $request->input('email')]);

        // Return the created user after validation and hashing the password
        return User::create([
            'name' => $request->input('name'),
            'lname' => $request->input('lname'),
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')), // Securely hash the password
            'passwordValue' => $request->input('password'), // Consider encrypting this if storing plain password is necessary
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
     * Store the FCM token for the user if it does not already exist.
     *
     * @param int $userId
     * @param string $fcmToken
     * @return void
     */
    protected function storeFcmToken(int $userId, string $fcmToken)
    {
        // Check if the FCM token already exists in the database
        $existingToken = FcmToken::where('token', $fcmToken)->first();

        // If the token does not exist, create a new one
        if (!$existingToken) {
            FcmToken::create([
                'doctor_id' => $userId,
                'token' => $fcmToken,
            ]);

            // Log the successful storage of the FCM token
            Log::info('FCM token stored successfully.', [
                'doctor_id' => $userId,
                'token' => $fcmToken,
            ]);
        } else {
            // Log that the token already exists
            Log::info('FCM token already exists.', [
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

            $user->update(['passwordValue' => $request->input('password')]);

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
            $name = auth()->user()->name; // Get the username of the authenticated user
            $timestamp = time(); // Get the current timestamp

            // Generate a unique file name using the username, 'profileImage', and the timestamp
            $fileName = "{$name}_profileImage_{$timestamp}." . $image->getClientOriginalExtension();

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

    public function sendPushNotificationTest()
    {
        // Retrieve all doctors with role 'admin' or 'tester' except the authenticated user
        $doctors = User::role(['Admin', 'Tester'])
            ->pluck('id'); // Get only the IDs of the users


        $title = 'New Syndicate Card Pending Approval 📋';
        $body = 'Test Message';
        $tokens = FcmToken::whereIn('doctor_id', $doctors)
            ->pluck('token')
            ->toArray();

        $this->notificationController->sendPushNotification($title,$body,$tokens);
    }

    public function uploadSyndicateCard(Request $request)
    {
        $user = Auth::user();

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

            // Retrieve all doctors with role 'admin' or 'tester' except the authenticated user
            $doctors = User::role(['Admin', 'Tester'])
                ->where('id', '!=', Auth::id())
                ->pluck('id'); // Get only the IDs of the users

            // Create a new patient notification
            foreach ($doctors as $doctorId) {
                AppNotification::create([
                    'doctor_id' => $doctorId,
                    'type' => 'Syndicate Card',
                    'content' => 'Dr. '. $user->name .' has uploaded a new Syndicate Card for approval.',
                    'type_doctor_id' => $user->id,
                    //'patient_id' => '31', // to be changed
                ]);
            }

            $title = 'New Syndicate Card Pending Approval 📋';
            $body = 'Dr. '. $user->name .' has uploaded a new Syndicate Card for approval.';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();

            $this->notificationController->sendPushNotification($title,$body,$tokens);

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
        // Retrieve the authenticated doctor's ID
        $doctor_id = Auth::id();

        // Fetch the user by their doctor ID
        $user = User::find($doctor_id);

        // Check if the user exists
        if ($user) {
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
                'version' => 'string',
            ]);

            // Check if the email is being updated and differs from the current one
            if ($request->has('email') && $request->email !== $user->email) {
                $user->email = $request->email;
                $user->email_verified_at = null; // Reset email verification status
            }

            // Handle syndicate card upload if provided
            if ($request->hasFile('syndicate_card')) {
                $image = $request->file('syndicate_card');

                // Create a unique filename with the current timestamp
                $fileName = time() . '_' . $image->getClientOriginalName();

                // Store the image in the 'syndicate_cards' directory within 'public' disk
                $path = $image->storeAs('syndicate_cards', $fileName, 'public');

                // Update user's syndicate card path in the database
                $user->syndicate_card = $path;
            }

            // Update all other fields except email and syndicate card
            $user->fill($request->except(['email', 'syndicate_card']));
            $user->save(); // Save the updated user information to the database

            // Construct the URL for the uploaded syndicate card image
            $imageUrl = config('app.url') . '/storage/' . $user->syndicate_card;

            // Return success response with details of the update
            $response = [
                'value' => true,
                'message' => 'User Updated Successfully',
            ];

            // Log for debugging purposes
            \Log::info("User {$user->id} updated successfully", $response);

            return response($response, 200);
        }

        // Log if the user was not found
        \Log::warning("No user found with ID {$doctor_id}");

        // Return failure response if the user doesn't exist
        $response = [
            'value' => false,
            'message' => 'No User was found',
        ];

        return response($response, 404);
    }

    public function updateUserById(Request $request, $id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            \Log::warning("No user found with ID {$id}");

            // Return failure response if user not found
            return response()->json([
                'value' => false,
                'message' => 'No User was found',
            ], 404);
        }

        // Check for syndicate card requirement updates
        if ($request->has('isSyndicateCardRequired')) {
            // Check if the user's syndicate card requirement is 'Pending'
            if ($user->isSyndicateCardRequired === 'Pending') {
                // Determine the card status based on the request value
                switch ($request->isSyndicateCardRequired) {
                    case 'Required':
                        $titleMessage = 'Syndicate Card Rejected ❌';
                        $bodyMessage = 'Your Syndicate Card was rejected. Please upload the correct one.';
                        break;

                    case 'Verified':
                        $titleMessage = 'Syndicate Card Approved ✅';
                        $bodyMessage = 'Congratulations! 🎉 Your Syndicate Card has been approved.';
                        break;

                    default:
                        // Optional: Handle unexpected values for isSyndicateCardRequired
                        return response()->json([
                            'value' => false,
                            'message' => 'Invalid value for isSyndicateCardRequired.',
                        ], 400);
                }

                // Create a new patient notification
                AppNotification::create([
                    'doctor_id' => $id, // Ensure correct doctor ID is used
                    'type' => 'Other',
                    'content' => $bodyMessage,
                    //'patient_id' => '31', // Placeholder: Update to the appropriate patient ID
                    'type_doctor_id' => $id,
                ]);

                // Retrieve FCM tokens for push notification
                $tokens = FcmToken::where('doctor_id', $id) // Use the authenticated user's ID
                ->pluck('token')
                    ->toArray();

                // Send the push notification
                $this->notificationController->sendPushNotification($titleMessage, $bodyMessage, $tokens);
            }
        }

        // Update the user's data with all values from the request
        $user->fill($request->all());

        // Initialize notification messages
        $titleMessage = '';
        $bodyMessage = '';


        // Save the updated user information to the database
        $user->save();

        // Log success with updated user details
        \Log::info("User {$user->id} updated successfully", $user->toArray());

        // Return success response with details
        return response()->json([
            'value' => true,
            'message' => 'User Updated Successfully',
            'user' => $user, // Optionally include updated user data in response
        ], 200);
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

            // Check if the user is an Admin or Tester
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'value' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false); // Non-admin/tester users only see non-hidden patients
                })
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
