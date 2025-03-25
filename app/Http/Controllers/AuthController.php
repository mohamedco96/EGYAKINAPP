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
use Kreait\Firebase\Messaging\CloudMessage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\NotificationController;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }


    public function register(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'lname' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users',
                    'password' => [
                        'required',
                        'string',
                        'min:6' // At least 8 chars, 1 letter and 1 number
                    ],
                    'age' => 'nullable|integer|min:18|max:100',
                    'specialty' => 'nullable|string|max:255',
                    'workingplace' => 'nullable|string|max:255',
                    'phone' => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                    'job' => 'nullable|string|max:255',
                    'highestdegree' => 'nullable|string|max:255',
                    'registration_number' => 'required|string|unique:users',
                    'fcmToken' => 'nullable|string|max:255'
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Create user
                $user = $this->createUser($validated);

                // Store FCM token if provided
                if (isset($validated['fcmToken'])) {
                    $this->storeFcmToken($user->id, $validated['fcmToken']);
                }

                // Generate token
                $token = $user->createToken('auth_token')->plainTextToken;

                DB::commit();

                Log::info('User registered successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                return response()->json([
                    'value' => true,
                    'message' => 'User Created Successfully',
                    'token' => $token,
                    'data' => $user
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email')
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Registration failed'
            ], 500);
        }
    }

    /**
     * Create a new user with the provided validated data.
     *
     * @param array $data
     * @return \App\Models\User
     */
    protected function createUser(array $data)
    {
        // Sanitize inputs
        $sanitized = array_map('trim', $data);
        
        return User::create([
            'name' => $sanitized['name'],
            'lname' => $sanitized['lname'],
            'email' => strtolower($sanitized['email']),
            'password' => Hash::make($sanitized['password']),
            'passwordValue' => encrypt($sanitized['password']), // Encrypt stored password
            'age' => $sanitized['age'] ?? null,
            'specialty' => $sanitized['specialty'] ?? null,
            'workingplace' => $sanitized['workingplace'] ?? null,
            'phone' => $sanitized['phone'] ?? null,
            'job' => $sanitized['job'] ?? null,
            'highestdegree' => $sanitized['highestdegree'] ?? null,
            'registration_number' => $sanitized['registration_number'],
        ]);
    }

    /**
     * Store the FCM token for the user if it does not already exist.
     *
     * @param int $userId
     * @param string $fcmToken
     * @return void
     */
    protected function storeFcmToken($userId, $token)
    {
        // Validate token format
        if (!preg_match('/^[a-zA-Z0-9:_-]{1,255}$/', $token)) {
            Log::warning('Invalid FCM token format', [
                'user_id' => $userId,
                'token' => substr($token, 0, 32) . '...' // Log only part of token
            ]);
            return;
        }

        try {
            FcmToken::updateOrCreate(
                ['token' => $token],
                ['doctor_id' => $userId]
            );

            Log::info('FCM token stored', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('FCM token storage failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }


    public function login(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'email' => 'required|email|max:255',
                    'password' => 'required|string|min:8',
                    'fcmToken' => 'nullable|string|max:255'
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            // Normalize email to lowercase
            $email = strtolower($validated['email']);

            // Rate limiting for failed attempts
            $key = 'login_attempts_' . $email;
            $attempts = Cache::get($key, 0);

            if ($attempts > 5) {
                Log::warning('Login attempts exceeded for email', ['email' => $email]);
                return response()->json([
                    'value' => false,
                    'message' => 'Too many login attempts. Please try again later.'
                ], 429);
            }

            // Attempt authentication
            if (!Auth::attempt(['email' => $email, 'password' => $validated['password']])) {
                Cache::put($key, $attempts + 1, now()->addMinutes(15));
                
                Log::warning('Failed login attempt', ['email' => $email]);
                return response()->json([
                    'value' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = Auth::user();

            // Clear failed attempts on successful login
            Cache::forget($key);

            // Store FCM token if provided
            if (isset($validated['fcmToken'])) {
                $this->storeFcmToken($user->id, $validated['fcmToken']);
            }

            // Generate new token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $email
            ]);

            return response()->json([
                'value' => true,
                'message' => 'User Logged In Successfully',
                'token' => $token,
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email')
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Login failed'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Revoke all tokens
            $user->tokens()->delete();

            Log::info('User logged out successfully', [
                'user_id' => $user->id
            ]);

            return response()->json([
                'value' => true,
                'message' => 'User Logged Out Successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }


    /**
     * Change the authenticated user's password.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'current_password' => 'required|string|min:6',
                    'new_password' => [
                        'required',
                        'string',
                        'min:6',
                        'different:current_password'
                    ]
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            $user = Auth::user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                Log::warning('Invalid current password in change attempt', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'value' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            DB::beginTransaction();
            try {
                $user->password = Hash::make($validated['new_password']);
                $user->passwordValue = encrypt($validated['new_password']);
                $user->save();

                DB::commit();

                Log::info('Password changed successfully', [
                    'user_id' => $user->id
                ]);

                return response()->json([
                    'value' => true,
                    'message' => 'Password changed successfully'
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Password change failed'
            ], 500);
        }
    }

    protected function validateFileUpload($file, $maxSize = 2048)
    {
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type');
        }

        if ($file->getSize() > $maxSize * 1024) {
            throw new \Exception('File size exceeds limit');
        }

        return true;
    }

    public function uploadProfileImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if (!$request->hasFile('image')) {
                throw new \Exception('No image file provided');
            }

            $image = $request->file('image');
            $this->validateFileUpload($image);

            $user = auth()->user();
            $fileName = sprintf('%s_profileImage_%s.%s', 
                $user->name,
                time(),
                $image->getClientOriginalExtension()
            );

            DB::beginTransaction();
            try {
                $path = $image->storeAs('profile_images', $fileName, 'public');
                
                // Delete old image if exists
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }
                
                $user->update(['image' => $path]);
                
                DB::commit();

                $imageUrl = config('app.url') . '/storage/' . $path;

                Log::info('Profile image updated', [
                    'user_id' => $user->id,
                    'path' => $path
                ]);

                return response()->json([
                    'value' => true,
                    'message' => 'Profile image uploaded successfully.',
                    'image' => $imageUrl,
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Profile image upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'value' => false,
                'message' => $e->getMessage()
            ], 400);
        }
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

            // Create notifications for all doctors at once
            $notifications = array_map(function($doctorId) use ($user) {
                return [
                    'doctor_id' => $doctorId,
                    'type' => 'Syndicate Card',
                    'content' => 'Dr. ' . $user->name . ' has uploaded a new Syndicate Card for approval.',
                    'type_doctor_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $doctors);

            AppNotification::insert($notifications);

            $title = 'New Syndicate Card Pending Approval 📋';
            $body = 'Dr. ' . $user->name . ' has uploaded a new Syndicate Card for approval.';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();

            $this->notificationController->sendPushNotification($title, $body, $tokens);

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
        try {
            $user = User::findOrFail(Auth::id());

            try {
                // Validate update data
                $validated = $request->validate([
                    'name' => 'sometimes|string|max:255',
                    'lname' => 'sometimes|string|max:255',
                    'email' => 'sometimes|email|unique:users,email,' . $user->id,
                    'age' => 'sometimes|integer|min:18|max:100',
                    'specialty' => 'sometimes|string|max:255',
                    'workingplace' => 'sometimes|string|max:255',
                    'phone' => 'sometimes|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                    'job' => 'sometimes|string|max:255',
                    'highestdegree' => 'sometimes|string|max:255',
                    'registration_number' => 'sometimes|string|unique:users,registration_number,' . $user->id,
                    'version' => 'sometimes|string|max:50',
                ]);
            } catch (ValidationException $e) {
                return response()->json([
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Handle email update
                if (isset($validated['email']) && $validated['email'] !== $user->email) {
                    $validated['email'] = strtolower($validated['email']);
                    $validated['email_verified_at'] = null;
                }

                // Sanitize inputs
                $sanitized = array_map('trim', $validated);
                
                // Update user
                $user->fill($sanitized);
                $user->save();

                DB::commit();

                Log::info('User updated', [
                    'user_id' => $user->id,
                    'fields' => array_keys($validated)
                ]);

                return response()->json([
                    'value' => true,
                    'message' => 'User Updated Successfully'
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No User was found'
            ], 404);
        }
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
            'data' => $user, // Optionally include updated user data in response
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
        try {
            // Eager load relationships and select specific fields
            $user = User::with([
                'patients' => function($q) {
                    $q->select('id', 'doctor_id');
                },
                'score:id,doctor_id,score',
                'posts:id,doctor_id',
                'saves:id,doctor_id'
            ])
            ->select('id', 'name', 'lname', 'image', 'email', 'specialty', 'workingplace')
            ->findOrFail($id);

            // Get counts using relationship counts
            $patientCount = $user->patients()->count();
            $postsCount = $user->posts()->count();
            $savedPostsCount = $user->saves()->count();
            $scoreValue = optional($user->score)->score ?? 0;

            // Get image URL
            $imageUrl = $user->image ? config('app.url') . '/storage/' . $user->image : null;

            // Prepare response maintaining exact structure
            $response = [
                'value' => true,
                'patient_count' => strval($patientCount),
                'score_value' => strval($scoreValue),
                'posts_count' => strval($postsCount),
                'saved_posts_count' => strval($savedPostsCount),
                'image' => $imageUrl,
                'data' => $user
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('Error fetching user profile', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No user was found'
            ], 404);
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

            $postsCount = $user->posts()->count();

            $savedPostsCount = $user->saves()->count();

            // Get the user's score value
            $scoreValue = optional($user->score)->score ?? 0;

            // Prepare the response data
            $responseData = [
                'value' => true,
                'patient_count' => strval($patientCount) ?? 0,
                'score_value' => strval($scoreValue),
                'posts_count' => strval($postsCount) ?? 0,
                'saved_posts_count' => strval($savedPostsCount) ?? 0,
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
            $user = User::select('id')
                ->with(['roles:id,name'])
                ->findOrFail($id);

            $isAdminOrTester = $user->hasRole(['Admin', 'Tester']);

            // Optimize query with eager loading and specific selections
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->when(!$isAdminOrTester, function ($query) {
                    return $query->where('hidden', false);
                })
                ->with([
                    'doctor:id,name,lname,image,syndicate_card,isSyndicateCardRequired',
                    'status' => function ($query) {
                        $query->select('id', 'patient_id', 'key', 'status')
                            ->whereIn('key', ['submit_status', 'outcome_status']);
                    },
                    'answers' => function ($query) {
                        $query->select('id', 'patient_id', 'answer', 'question_id')
                            ->whereIn('question_id', [1, 2]);
                    }
                ])
                ->latest('updated_at')
                ->get();

            // Transform maintaining exact structure
            $transformedPatients = $currentPatients->map(function ($patient) {
                return [
                    'id' => $patient->id,
                    'doctor_id' => $patient->doctor_id,
                    'name' => optional($patient->answers->where('question_id', 1)->first())->answer,
                    'hospital' => optional($patient->answers->where('question_id', 2)->first())->answer,
                    'updated_at' => $patient->updated_at,
                    'doctor' => $patient->doctor,
                    'sections' => [
                        'patient_id' => $patient->id,
                        'submit_status' => optional($patient->status->where('key', 'submit_status')->first())->status ?? false,
                        'outcome_status' => optional($patient->status->where('key', 'outcome_status')->first())->status ?? false,
                    ]
                ];
            });

            // Paginate maintaining exact structure
            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 10;
            
            $paginatedData = new LengthAwarePaginator(
                $transformedPatients->forPage($page, $perPage),
                $transformedPatients->count(),
                $perPage
            );

            Log::info('Retrieved patients for doctor profile', [
                'doctor_id' => $id,
                'count' => $transformedPatients->count()
            ]);

            return response()->json([
                'value' => true,
                'data' => $paginatedData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving patients', [
                'doctor_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve patients'
            ], 500);
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
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired');
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
