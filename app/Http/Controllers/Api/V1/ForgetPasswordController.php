<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\ForgetPasswordController as ModuleForgetPasswordController;
use App\Modules\Auth\Requests\ForgetPasswordRequest;

class ForgetPasswordController extends Controller
{
    protected $forgetPasswordController;

    public function __construct(ModuleForgetPasswordController $forgetPasswordController)
    {
        $this->forgetPasswordController = $forgetPasswordController;
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        return $this->forgetPasswordController->forgotPassword($request);
    }
}
