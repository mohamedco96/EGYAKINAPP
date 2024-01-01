<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordVerificationNotification;
use Otp;
use Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    private $otp;

    public function __construct(){
        $this->middleware('auth');
        $this->otp = new Otp;
    }

    public function resetpasswordverification(ResetPasswordRequest $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'User not authenticated.'], 401);
        }
    
        $otp2 = $this->otp->validate(auth()->user()->email, $request->otp);
    
        if (!$otp2->status) {
            return response()->json(['error' => $otp2], 401);
        }
    
        return response()->json(['success' => true], 200);
    }

    public function resetpassword(ResetPasswordRequest $request){
        $verify =  DB::table('otps')->where('valid', '=', true);
        if($verify){
            return response()->json(['error' => 'This email not verified to change password'], 401);
        }

        $user = User::where('email', Auth::user()->email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        $success['success'] = true;
        return response()->json($success,200);
    }
}
