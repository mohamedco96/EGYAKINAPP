<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\OtpController as ModuleOtpController;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    protected $otpController;

    public function __construct(ModuleOtpController $otpController)
    {
        $this->otpController = $otpController;
    }

    public function verifyOtp(Request $request)
    {
        return $this->otpController->verifyOtp($request);
    }

    public function sendOtp(Request $request)
    {
        return $this->otpController->sendOtp($request);
    }

    public function resendOtp(Request $request)
    {
        return $this->otpController->resendOtp($request);
    }
}
