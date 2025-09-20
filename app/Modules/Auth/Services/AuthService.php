<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use App\Notifications\WelcomeMailNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Register a new user
     */
    public function register(array $validatedData): array
    {
        return DB::transaction(function () use ($validatedData) {
            // Create user
            $user = $this->createUser($validatedData);

            // Store FCM token if provided
            if (isset($validatedData['fcmToken'])) {
                $this->storeFcmToken(
                    $user->id,
                    $validatedData['fcmToken'],
                    $validatedData['deviceId'] ?? null,
                    $validatedData['deviceType'] ?? null,
                    $validatedData['appVersion'] ?? null
                );
            }

            // Retrieve the user from the database to get default values
            $user = User::find($user->id);

            // Send welcome email notification
            try {
                $user->notify(new WelcomeMailNotification());
                Log::info('Welcome email sent successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail registration if email sending fails
            }

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'value' => true,
                'message' => __('api.user_created_successfully'),
                'token' => $token,
                'data' => $user,
            ];
        });
    }

    /**
     * Authenticate user login
     */
    public function login(array $validatedData): array
    {
        // Normalize email to lowercase
        $email = strtolower($validatedData['email']);

        // Rate limiting for failed attempts
        $key = 'login_attempts_'.$email;
        $attempts = Cache::get($key, 0);

        if ($attempts > 5) {
            Log::warning('Login attempts exceeded for email', ['email' => $email]);

            return [
                'value' => false,
                'message' => __('api.too_many_login_attempts'),
                'status_code' => 429,
            ];
        }

        // Attempt authentication
        if (! Auth::attempt(['email' => $email, 'password' => $validatedData['password']])) {
            Cache::put($key, $attempts + 1, now()->addMinutes(15));

            Log::warning('Failed login attempt', ['email' => $email]);

            return [
                'value' => false,
                'message' => __('api.invalid_credentials'),
                'status_code' => 401,
            ];
        }

        $user = Auth::user();

        // Clear failed attempts on successful login
        Cache::forget($key);

        // Store FCM token if provided
        if (isset($validatedData['fcmToken'])) {
            $this->storeFcmToken(
                $user->id,
                $validatedData['fcmToken'],
                $validatedData['deviceId'] ?? null,
                $validatedData['deviceType'] ?? null,
                $validatedData['appVersion'] ?? null
            );
        }

        // Generate new token
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return [
            'value' => true,
            'message' => __('api.user_logged_in_successfully'),
            'token' => $token,
            'data' => $user,
            'status_code' => 200,
        ];
    }

    /**
     * Logout user
     */
    public function logout(): array
    {
        $user = Auth::user();

        // Revoke only the current token
        request()->user()->currentAccessToken()->delete();

        Log::info('User logged out successfully', [
            'user_id' => $user->id,
        ]);

        return [
            'value' => true,
            'message' => __('api.user_logged_out_successfully'),
            'status_code' => 200,
        ];
    }

    /**
     * Change user password
     */
    public function changePassword(array $validatedData): array
    {
        $user = Auth::user();

        if (! Hash::check($validatedData['current_password'], $user->password)) {
            Log::warning('Invalid current password in change attempt', [
                'user_id' => $user->id,
            ]);

            return [
                'value' => false,
                'message' => __('api.current_password_incorrect'),
                'status_code' => 400,
            ];
        }

        return DB::transaction(function () use ($validatedData, $user) {
            $user->password = Hash::make($validatedData['new_password']);
            $user->save();

            Log::info('Password changed successfully', [
                'user_id' => $user->id,
            ]);

            return [
                'value' => true,
                'message' => __('api.password_changed_successfully'),
                'status_code' => 200,
            ];
        });
    }

    /**
     * Upload profile image
     */
    public function uploadProfileImage(UploadedFile $image): array
    {
        $this->validateFileUpload($image);

        $user = auth()->user();
        $fileName = sprintf('%s_profileImage_%s.%s',
            $user->name,
            time(),
            $image->getClientOriginalExtension()
        );

        return DB::transaction(function () use ($image, $fileName, $user) {
            $path = $image->storeAs('profile_images', $fileName, 'public');

            // Delete old image if exists
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $user->update(['image' => $path]);

            $imageUrl = config('app.url').'/storage/'.$path;

            Log::info('Profile image updated', [
                'user_id' => $user->id,
                'path' => $path,
            ]);

            return [
                'value' => true,
                'message' => __('api.profile_image_uploaded_successfully'),
                'image' => $imageUrl,
                'status_code' => 200,
            ];
        });
    }

    /**
     * Upload syndicate card
     */
    public function uploadSyndicateCard(UploadedFile $syndicateCard): array
    {
        $user = Auth::user();
        $fileName = time().'_'.$syndicateCard->getClientOriginalName();

        return DB::transaction(function () use ($syndicateCard, $fileName, $user) {
            $path = $syndicateCard->storeAs('syndicate_card', $fileName, 'public');
            $imageUrl = config('app.url').'/storage/'.$path;

            // Update user's syndicate card
            $user->update(['syndicate_card' => $path, 'isSyndicateCardRequired' => 'Pending']);

            // Send notifications to admins
            $this->sendSyndicateCardNotifications($user);

            return [
                'value' => true,
                'message' => __('api.syndicate_card_uploaded_successfully'),
                'image' => $imageUrl,
                'status_code' => 200,
            ];
        });
    }

    /**
     * Update user profile
     */
    public function updateProfile(array $validatedData): array
    {
        $user = User::findOrFail(Auth::id());

        return DB::transaction(function () use ($validatedData, $user) {
            // Handle email update
            if (isset($validatedData['email']) && $validatedData['email'] !== $user->email) {
                $validatedData['email'] = strtolower($validatedData['email']);
                $validatedData['email_verified_at'] = null;
            }

            // Sanitize inputs
            $sanitized = array_map('trim', $validatedData);

            // Update user
            $user->fill($sanitized);
            $user->save();

            Log::info('User updated', [
                'user_id' => $user->id,
                'fields' => array_keys($validatedData),
            ]);

            return [
                'value' => true,
                'message' => __('api.user_updated_successfully'),
                'status_code' => 200,
            ];
        });
    }

    /**
     * Update user by ID (Admin function)
     */
    public function updateUserById(int $id, array $requestData): array
    {
        $user = User::find($id);

        if (! $user) {
            Log::warning("No user found with ID {$id}");

            return [
                'value' => false,
                'message' => __('api.no_user_found'),
                'status_code' => 404,
            ];
        }

        // Handle syndicate card requirement updates
        if (isset($requestData['isSyndicateCardRequired'])) {
            $this->handleSyndicateCardUpdate($user, $requestData['isSyndicateCardRequired']);
        }

        // Update the user's data
        $user->fill($requestData);
        $user->save();

        Log::info("User {$user->id} updated successfully", $user->toArray());

        return [
            'value' => true,
            'message' => __('api.user_updated_successfully'),
            'data' => $user,
            'status_code' => 200,
        ];
    }

    /**
     * Get all users
     */
    public function getAllUsers(): array
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return [
                'value' => false,
                'message' => __('api.no_user_found'),
                'status_code' => 404,
            ];
        }

        return [
            'value' => true,
            'data' => $users,
            'status_code' => 200,
        ];
    }

    /**
     * Get user profile by ID
     */
    public function getUserProfile(int $id): array
    {
        try {
            // Eager load relationships and select specific fields
            $user = User::with([
                'patients' => function ($q) {
                    $q->select('id', 'doctor_id');
                },
                'score:id,doctor_id,score',
                'posts:id,doctor_id',
                'saves:id,doctor_id',
            ])
                ->select('id', 'name', 'lname', 'image', 'email', 'specialty', 'workingplace')
                ->findOrFail($id);

            // Get counts from already loaded collections to avoid additional queries
            $patientCount = $user->patients->count();
            $postsCount = $user->posts->count();
            $savedPostsCount = $user->saves->count();
            $scoreValue = optional($user->score)->score ?? 0;

            // Get image URL
            $imageUrl = $user->image ? config('app.url').'/storage/'.$user->image : null;

            return [
                'value' => true,
                'patient_count' => strval($patientCount),
                'score_value' => strval($scoreValue),
                'posts_count' => strval($postsCount),
                'saved_posts_count' => strval($savedPostsCount),
                'image' => $imageUrl,
                'data' => $user,
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching user profile', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => __('api.no_user_found'),
                'status_code' => 404,
            ];
        }
    }

    /**
     * Get another user's profile
     */
    public function getAnotherUserProfile(int $id): array
    {
        $user = User::with('patients')->find($id);

        if (! $user) {
            return [
                'value' => false,
                'message' => __('api.no_user_found'),
                'status_code' => 404,
            ];
        }

        $imageUrl = config('app.url').'/storage/'.$user->image;
        $patientCount = $user->patients()->count();
        $postsCount = $user->feedPosts()->count();
        $savedPostsCount = $user->saves()->count();
        $scoreValue = optional($user->score)->score ?? 0;

        return [
            'value' => true,
            'patient_count' => strval($patientCount) ?? 0,
            'score_value' => strval($scoreValue),
            'posts_count' => strval($postsCount) ?? 0,
            'saved_posts_count' => strval($savedPostsCount) ?? 0,
            'image' => $imageUrl,
            'data' => $user,
            'status_code' => 200,
        ];
    }

    /**
     * Get doctor's patients for profile
     */
    public function getDoctorPatients(int $id): array
    {
        try {
            $user = User::select('id')
                ->with(['roles:id,name'])
                ->findOrFail($id);

            $isAdminOrTester = $user->hasRole(['Admin', 'Tester']);

            // Optimize query with eager loading and specific selections
            $currentPatients = $user->patients()
                ->select('id', 'doctor_id', 'updated_at')
                ->when(! $isAdminOrTester, function ($query) {
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
                    },
                ])
                ->latest('updated_at');

            // Get paginated results
            $perPage = 10;
            $paginatedPatients = $currentPatients->paginate($perPage);

            // Transform the paginated results
            $transformedData = collect($paginatedPatients->items())->map(function ($patient) {
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
                    ],
                ];
            })->values()->all();

            // Create a new paginator with the transformed data
            $result = new LengthAwarePaginator(
                $transformedData,
                $paginatedPatients->total(),
                $perPage,
                $paginatedPatients->currentPage(),
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );

            Log::info('Retrieved patients for doctor profile', [
                'doctor_id' => $id,
                'count' => $paginatedPatients->total(),
            ]);

            return [
                'value' => true,
                'data' => $result,
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error retrieving patients', [
                'doctor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => __('api.failed_to_retrieve_patients'),
                'status_code' => 500,
            ];
        }
    }

    /**
     * Get doctor's score history
     */
    public function getDoctorScoreHistory(int $id): array
    {
        try {
            $user = User::find($id);

            if (! $user) {
                return [
                    'value' => false,
                    'message' => 'User not found',
                    'status_code' => 404,
                ];
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

            Log::info('Successfully retrieved score history for doctor.', ['doctor_id' => $id]);

            return [
                'value' => true,
                'data' => $transformedPatientsPaginated,
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error retrieving score history for doctor.', [
                'doctor_id' => $id,
                'exception' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => __('api.failed_to_retrieve_score_history'),
                'status_code' => 500,
            ];
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): array
    {
        $user = User::find($id);

        if (! $user) {
            return [
                'value' => false,
                'message' => __('api.no_user_found'),
                'status_code' => 404,
            ];
        }

        return DB::transaction(function () use ($user, $id) {
            // Delete the user
            $user->delete();

            // Delete related records from other tables
            $this->deleteUserRelatedData($id);

            return [
                'value' => true,
                'message' => __('api.user_deleted_successfully'),
                'status_code' => 200,
            ];
        });
    }

    /**
     * Create a new user with the provided validated data
     */
    protected function createUser(array $data): User
    {
        // Sanitize inputs
        $sanitized = array_map('trim', $data);

        return User::create([
            'name' => $sanitized['name'],
            'lname' => $sanitized['lname'],
            'email' => strtolower($sanitized['email']),
            'password' => Hash::make($sanitized['password']),
            'passwordValue' => encrypt($sanitized['password']),
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
     * Store the FCM token for the user if it does not already exist
     */
    protected function storeFcmToken(int $userId, string $token, ?string $deviceId = null, ?string $deviceType = null, ?string $appVersion = null): void
    {
        // Validate token format
        if (! preg_match('/^[a-zA-Z0-9:_-]+$/', $token) || strlen($token) === 0) {
            Log::warning('Invalid FCM token format', [
                'user_id' => $userId,
                'token' => substr($token, 0, 32).'...',
                'device_id' => $deviceId,
            ]);

            return;
        }

        // Validate device ID format if provided
        if ($deviceId && ! preg_match('/^[a-zA-Z0-9_-]{10,50}$/', $deviceId)) {
            Log::warning('Invalid device ID format', [
                'user_id' => $userId,
                'device_id' => $deviceId,
            ]);

            return;
        }

        try {
            // Prepare data for storage
            $tokenData = ['doctor_id' => $userId, 'token' => $token];

            if ($deviceId) {
                $tokenData['device_id'] = $deviceId;
            }

            if ($deviceType) {
                $tokenData['device_type'] = strtolower($deviceType);
            }

            if ($appVersion) {
                $tokenData['app_version'] = $appVersion;
            }

            // Use unique constraint on doctor_id + device_id if device_id is provided
            $uniqueFields = $deviceId
                ? ['doctor_id' => $userId, 'device_id' => $deviceId]
                : ['token' => $token];

            FcmToken::updateOrCreate($uniqueFields, $tokenData);

            Log::info('FCM token stored', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'device_type' => $deviceType,
                'app_version' => $appVersion,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM token storage failed', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate file upload
     */
    protected function validateFileUpload(UploadedFile $file, int $maxSize = 2048): void
    {
        if (! $file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type');
        }

        if ($file->getSize() > $maxSize * 1024) {
            throw new \Exception('File size exceeds limit');
        }
    }

    /**
     * Send notifications for syndicate card upload
     */
    protected function sendSyndicateCardNotifications(User $user): void
    {
        // Retrieve all doctors with role 'admin' or 'tester' except the authenticated user
        $doctors = User::role(['Admin', 'Tester'])
            ->where('id', '!=', Auth::id())
            ->with('fcmTokens:id,doctor_id,token')
            ->get();

        // Create notifications for all doctors at once
        $notifications = $doctors->map(function ($doctor) use ($user) {
            return [
                'doctor_id' => $doctor->id,
                'type' => 'Syndicate Card',
                'content' => 'Dr. '.$user->name.' has uploaded a new Syndicate Card for approval.',
                'type_doctor_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        AppNotification::insert($notifications);

        $title = __('api.syndicate_card_pending_approval');
        $body = __('api.doctor_uploaded_syndicate_card', ['name' => $user->name]);

        // Get tokens from eager loaded relationship
        $tokens = $doctors->pluck('fcmTokens.*.token')
            ->flatten()
            ->filter()
            ->toArray();

        $this->notificationService->sendPushNotification($title, $body, $tokens);
    }

    /**
     * Handle syndicate card status updates
     */
    protected function handleSyndicateCardUpdate(User $user, string $status): void
    {
        if ($user->isSyndicateCardRequired !== 'Pending') {
            return;
        }

        switch ($status) {
            case 'Required':
                $titleMessage = __('api.syndicate_card_rejected');
                $bodyMessage = __('api.syndicate_card_rejected_message');
                break;

            case 'Verified':
                $titleMessage = __('api.syndicate_card_approved');
                $bodyMessage = __('api.syndicate_card_approved_message');
                break;

            default:
                return;
        }

        // Create notification
        AppNotification::create([
            'doctor_id' => $user->id,
            'type' => 'Other',
            'content' => $bodyMessage,
            'type_doctor_id' => $user->id,
        ]);

        // Send push notification
        $tokens = FcmToken::where('doctor_id', $user->id)
            ->pluck('token')
            ->toArray();

        $this->notificationService->sendPushNotification($titleMessage, $bodyMessage, $tokens);
    }

    /**
     * Delete user related data
     */
    protected function deleteUserRelatedData(int $userId): void
    {
        // Import needed classes at the top of the file when implementing
        // PatientHistory::where('doctor_id', $userId)->delete();
        // Section::where('doctor_id', $userId)->delete();
        // Complaint::where('doctor_id', $userId)->delete();
        // Cause::where('doctor_id', $userId)->delete();
        // Risk::where('doctor_id', $userId)->delete();
        // Assessment::where('doctor_id', $userId)->delete();
        // Examination::where('doctor_id', $userId)->delete();
    }

    /**
     * Decrypt password (utility method)
     */
    public function decryptPassword(string $encryptedPassword): string
    {
        return decrypt($encryptedPassword);
    }
}
