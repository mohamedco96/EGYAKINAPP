<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Requests\EmailVerificationRequest;
use App\Modules\Auth\Services\OtpService;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * Send email verification notification
     */
    public function sendVerificationEmail()
    {
        try {

            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }

            $user = User::where('email', $user->email)->first();

            // Send verification notification
            $user->notify(new EmailVerificationNotification);

            Log::info('Email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification email sent successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
                'email' => $user->email ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify email using OTP
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        try {
            $user = Auth::user() ?? User::where('email', $request->email)->first();

            if (! $user) {
                throw new \Exception('User not found');
            }

            $otpValidation = $this->otpService->validateByIdentifier($user->email, $request->otp);

            if (! $otpValidation->status) {
                Log::warning('Invalid OTP attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP',
                ], 401);
            }

            // Mark email as verified
            $user->update(['email_verified_at' => now()]);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Email verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
