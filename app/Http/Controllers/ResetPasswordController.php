<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordVerificationNotification;
use Otp;
use Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    private $otp;
    private $email;

    public function __construct(Request $request)
    {
        $this->otp = new Otp;
        $this->email = $request->email;
    }

    /**
     * Verify reset password OTP.
     *
     * @param  \App\Http\Requests\ResetPasswordRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function resetpasswordverification(ResetPasswordRequest $request)
    {
        try {
            $otp2 = $this->otp->validate($this->email, $request->otp);

            if (!$otp2->status) {
                Log::warning('Reset password OTP verification failed', ['email' => $this->email]);
                return response()->json([
                    'value' => false,
                    'message' => $otp2,
                ], 401);
            }

            Log::info('Reset password OTP verified successfully', ['email' => $this->email]);

            return response()->json([
                'value' => true,
                'message' => 'Reset password OTP verified successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error verifying reset password OTP', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reset user password.
     *
     * @param  \App\Http\Requests\ResetPasswordRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function resetpassword(ResetPasswordRequest $request)
    {
        try {
            $verify = DB::table('otps')
                ->where('identifier', $this->email)
                ->where('valid', true)
                ->exists();

            if ($verify) {
                Log::warning('Email not verified for password reset', ['email' => $this->email]);
                return response()->json([
                    'value' => false,
                    'message' => 'This email is not verified to change the password.',
                ], 401);
            }

            $user = User::where('email', $this->email)->firstOrFail();
            $user->update(['password' => Hash::make($request->password)]);

            Log::info('Password reset successfully', ['email' => $this->email]);

            return response()->json([
                'value' => true,
                'message' => 'Password reset successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error resetting password', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
