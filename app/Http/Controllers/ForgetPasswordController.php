<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ForgetPasswordRequest;
use Otp;
use App\Models\User;
use App\Notifications\ResetPasswordVerificationNotification;
use Illuminate\Support\Facades\Log;

class ForgetPasswordController extends Controller
{
    /**
     * Send reset password notification to user's email.
     *
     * @param  \App\Http\Requests\ForgetPasswordRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(ForgetPasswordRequest $request)
    {
        try {
            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            if (!$user) {
                Log::warning('User with email not found for password reset', ['email' => $email]);
                return response()->json([
                    'value' => false,
                    'message' => 'User not found with this email address',
                ], 404);
            }

            $user->notify(new ResetPasswordVerificationNotification());

            Log::info('Reset password mail sent successfully', ['email' => $email]);

            return response()->json([
                'value' => true,
                'message' => 'Reset password Mail sent successfully to user',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error sending reset password mail', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'Error sending reset password mail',
            ], 500);
        }
    }
}
