<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use App\Notifications\ResetPasswordVerificationNotification;
use Otp;
use Hash;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    private $otp;
    private $email;
    
    public function __construct(Request $request)
    {
        $this->otp = new Otp;
        $this->email = $request->email;
    }
    
    public function resetpasswordverification(ResetPasswordRequest $request)
    {

        $otp2 = $this->otp->validate($email,$request->otp);
    
        if (!$otp2->status) {
            return response()->json(['error' => $otp2], 401);
        }
    
        return response()->json(['success' => true], 200);
    }

    public function resetpassword(ResetPasswordRequest $request){
        $verify =  DB::table('otps')
            ->where('email', '=', $email)
            ->where('valid', '=', true);
        if($verify){
            return response()->json(['error' => 'This email not verified to change password'], 401);
        }

        $user = User::where('email', $email)->first();
        $user->update(['password' => Hash::make($request->password)]);
        $success['success'] = true;
        return response()->json($success,200);
    }
}
