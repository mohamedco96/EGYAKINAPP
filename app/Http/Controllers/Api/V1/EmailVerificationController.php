<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\EmailVerificationController as ModuleEmailVerificationController;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    protected $emailVerificationController;

    public function __construct(ModuleEmailVerificationController $emailVerificationController)
    {
        $this->emailVerificationController = $emailVerificationController;
    }

    public function sendVerificationEmail(Request $request)
    {
        return $this->emailVerificationController->sendVerificationEmail($request);
    }

    public function verifyEmail(Request $request)
    {
        return $this->emailVerificationController->verifyEmail($request);
    }
}
