<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\ResetPasswordController as ModuleResetPasswordController;
use App\Modules\Auth\Requests\ResetPasswordRequest;

class ResetPasswordController extends Controller
{
    protected $resetPasswordController;

    public function __construct(ModuleResetPasswordController $resetPasswordController)
    {
        $this->resetPasswordController = $resetPasswordController;
    }

    public function resetpasswordverification(ResetPasswordRequest $request)
    {
        return $this->resetPasswordController->resetpasswordverification($request);
    }

    public function resetpassword(ResetPasswordRequest $request)
    {
        return $this->resetPasswordController->resetpassword($request);
    }
}
