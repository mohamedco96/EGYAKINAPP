<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\EmailVerificationController as V1EmailVerificationController;
use App\Modules\Auth\Requests\EmailVerificationRequest;

class EmailVerificationController extends Controller
{
    protected $emailVerificationController;

    public function __construct(V1EmailVerificationController $emailVerificationController)
    {
        $this->emailVerificationController = $emailVerificationController;
    }

    public function sendVerificationEmail(Request $request)
    {
        return $this->emailVerificationController->sendVerificationEmail($request);
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        return $this->emailVerificationController->verifyEmail($request);
    }
}
