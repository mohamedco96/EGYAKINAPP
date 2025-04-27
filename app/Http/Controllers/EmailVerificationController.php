<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EmailVerificationRequest;
use Otp;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    private $otp;

    public function __construct()
    {
        $this->otp = new Otp;
    }

    /**
     * Send email verification notification
     */
    public function sendVerificationEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $user = User::where('email', $request->email)->first();
            
            // Send verification notification
            $user->notify(new EmailVerificationNotification());

            Log::info('Email verification sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification email sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
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
            
            if (!$user) {
                throw new \Exception('User not found');
            }

            $otpValidation = $this->otp->validate($user->email, $request->otp);

            if (!$otpValidation->status) {
                Log::warning('Invalid OTP attempt', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid OTP'
                ], 401);
            }

            // Mark email as verified
            $user->update(['email_verified_at' => now()]);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
                'email' => $request->email ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Email verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}