<?php

namespace App\Services\Auth;

use App\Services\Auth\Interfaces\AuthServiceInterface;
use App\Repositories\User\UserRepository;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuthService implements AuthServiceInterface
{
    protected $userRepository;
    protected $notificationController;

    public function __construct(
        UserRepository $userRepository,
        NotificationController $notificationController
    ) {
        $this->userRepository = $userRepository;
        $this->notificationController = $notificationController;
    }

    public function register(array $data): array
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->create($data);

            if (isset($data['fcmToken'])) {
                $this->userRepository->storeFcmToken($user->id, $data['fcmToken']);
            }

            $user = $this->userRepository->findById($user->id);
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

    public function login(array $data): array
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
            $this->userRepository->storeFcmToken($user->id, $data['fcmToken']);
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

    public function logout($user): array
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
} 