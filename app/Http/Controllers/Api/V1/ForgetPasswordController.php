<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\ForgetPasswordController as ModuleForgetPasswordController;
use Illuminate\Http\Request;

class ForgetPasswordController extends Controller
{
    protected $forgetPasswordController;

    public function __construct(ModuleForgetPasswordController $forgetPasswordController)
    {
        $this->forgetPasswordController = $forgetPasswordController;
    }

    public function forgotPassword(Request $request)
    {
        return $this->forgetPasswordController->forgotPassword($request);
    }
}
