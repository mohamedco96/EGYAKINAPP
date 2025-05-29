<?php

namespace App\Services;

use App\Models\User;
use App\Models\FcmToken;
use App\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationController;

class AuthService
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function register(array $data)
    {
        DB::beginTransaction();
        try {
            $user = $this->createUser($data);

            if (isset($data['fcmToken'])) {
                $this->storeFcmToken($user->id, $data['fcmToken']);
            }

            $user = User::find($user->id);
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'value' => true,
                'message' => 'User Created Successfully',
                'token' => $token,
                'data' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(array $data)
    {
        $email = strtolower($data['email']);
        $key = 'login_attempts_' . $email;
        $attempts = Cache::get($key, 0);

        if ($attempts > 5) {
            Log::warning('Login attempts exceeded for email', ['email' => $email]);
            return [
                'value' => false,
                'message' => 'Too many login attempts. Please try again later.'
            ];
        }

        if (!Auth::attempt(['email' => $email, 'password' => $data['password']])) {
            Cache::put($key, $attempts + 1, now()->addMinutes(15));
            
            Log::warning('Failed login attempt', ['email' => $email]);
            return [
                'value' => false,
                'message' => 'Invalid credentials'
            ];
        }

        $user = Auth::user();
        Cache::forget($key);

        if (isset($data['fcmToken'])) {
            $this->storeFcmToken($user->id, $data['fcmToken']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('User logged in successfully', [
            'user_id' => $user->id,
            'email' => $email
        ]);

        return [
            'value' => true,
            'message' => 'User Logged In Successfully',
            'token' => $token,
            'data' => $user
        ];
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();

        Log::info('User logged out successfully', [
            'user_id' => $user->id
        ]);

        return [
            'value' => true,
            'message' => 'User Logged Out Successfully'
        ];
    }

    protected function createUser(array $data)
    {
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

    protected function storeFcmToken($userId, $token)
    {
        if (!preg_match('/^[a-zA-Z0-9:_-]{1,255}$/', $token)) {
            Log::warning('Invalid FCM token format', [
                'user_id' => $userId,
                'token' => substr($token, 0, 32) . '...'
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
} 