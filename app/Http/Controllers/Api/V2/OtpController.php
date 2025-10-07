<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\OtpController as V1OtpController;
use App\Modules\Auth\Requests\VerifyOtpRequest;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    protected $otpController;

    public function __construct(V1OtpController $otpController)
    {
        $this->otpController = $otpController;
    }

    public function verifyOtp(VerifyOtpRequest $request)
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
