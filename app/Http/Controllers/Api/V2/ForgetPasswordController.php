<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\ForgetPasswordController as V1ForgetPasswordController;
use App\Modules\Auth\Requests\ForgetPasswordRequest;

class ForgetPasswordController extends Controller
{
    protected $forgetPasswordController;

    public function __construct(V1ForgetPasswordController $forgetPasswordController)
    {
        $this->forgetPasswordController = $forgetPasswordController;
    }

    public function forgotPassword(ForgetPasswordRequest $request)
    {
        return $this->forgetPasswordController->forgotPassword($request);
    }
}
