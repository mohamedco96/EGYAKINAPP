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

        $otp2 = $this->otp->validate($this->email,$request->otp);
    
        if (!$otp2->status) {
            return response()->json(['error' => $otp2], 401);
        }
    
        return response()->json(['success' => true], 200);
    }

    public function resetpassword(ResetPasswordRequest $request)
{
    //$email = $request->email;

    $verify = DB::table('otps')
        ->where('identifier', $this->email)
        ->where('valid', true)
        ->exists();

    if ($verify) {
        return response()->json(['error' => 'This email is not verified to change the password'], 401);
    }

    $user = User::where('email', $this->email)->firstOrFail();
    $user->update(['password' => Hash::make($request->password)]);

    return response()->json(['success' => true], 200);
}
}
