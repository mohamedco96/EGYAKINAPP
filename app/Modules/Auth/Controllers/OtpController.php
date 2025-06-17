<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use App\Models\User;
use App\Modules\Auth\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected $otpService;
    
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }
    
    /**
     * Send OTP verification code to user's email
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendOtp(): JsonResponse
    {
        $user = Auth::user();
        
        $user = User::where('email', $user->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address',
            ], 404);
        }
        
        $result = $this->otpService->sendOtpEmail($user);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email address',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP. Please try again later.',
            ], 500);
        }
    }
    
    /**
     * Verify OTP code
     * 
     * @param VerifyOtpRequest $request
     * @return JsonResponse
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address',
            ], 404);
        }
        
        $isValid = $this->otpService->verifyOtp($user, $request->otp);
        
        if ($isValid) {
            // Mark user as verified
            $user->email_verified_at = Carbon::now();
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 422);
        }
    }
    
    /**
     * Resend OTP verification code
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $user = User::where('email', $user->email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address',
            ], 404);
        }
        
        $result = $this->otpService->sendOtpEmail($user);
        
        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'OTP has been resent to your email address',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend OTP. Please try again later.',
            ], 500);
        }
    }
}