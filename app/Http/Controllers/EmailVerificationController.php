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
     * Send email verification notification to the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendEmailVerification(Request $request)
    {
        try {
            $request->user()->notify(new EmailVerificationNotification());

            Log::info('Email verification mail sent', ['user_id' => $request->user()->id]);

            return response()->json([
                'value' => true,
                'message' => 'Verification Mail sent to user',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error sending email verification mail', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'Error sending verification mail',
            ], 500);
        }
    }

    /**
     * Verify email using OTP.
     *
     * @param  \App\Http\Requests\EmailVerificationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function email_verification(EmailVerificationRequest $request)
    {
        try {
            $otp2 = $this->otp->validate(Auth::user()->email, $request->otp);

            if (!$otp2->status) {
                Log::warning('Email verification failed', ['user_id' => Auth::user()->id]);
                return response()->json([
                    'value' => false,
                    'message' => 'OTP does not exist',
                ], 401);
            }

            $user = User::where('email', Auth::user()->email)->first();
            $user->update(['email_verified_at' => now()]);

            Log::info('Email verified successfully', ['user_id' => Auth::user()->id]);
            return response()->json([
                'value' => true,
                'message' => 'User Email verified successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying email', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'Error verifying email',
            ], 500);
        }
    }
}
