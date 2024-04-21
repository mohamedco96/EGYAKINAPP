<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EmailVerificationRequest;
use Otp;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    private $otp;

    public function __construct(){
        $this->otp = new Otp;
    }
   // $user->notify(new EmailVerificationNotification());
   //$doctor_id = Auth::id();
    public function sendEmailVerification(Request $request){
        $request->user()->notify(new EmailVerificationNotification());
        $success['success'] = true;
        return response()->json($success,200);
    }

    public function email_verification(EmailVerificationRequest $request){
<<<<<<< HEAD
        try {
            $otp2 = $this->otp->validate(Auth::user()->email, $request->otp);

            if(!$otp2->status){
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
=======
        $otp2 = $this->otp->validate(Auth::user()->email,$request->otp);
        if(!$otp2->status){
            return response()->json(['error' => $otp2], 401);
>>>>>>> parent of 88fce4d (Update EmailVerificationController.php)
        }

        $user = User::where('email', Auth::user()->email)->first();
        $user->update(['email_verified_at' => now()]);
        $success['success'] = true;
        return response()->json($success,200);
    }
}
