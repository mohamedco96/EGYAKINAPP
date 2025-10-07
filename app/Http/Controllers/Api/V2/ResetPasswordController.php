<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\ResetPasswordController as V1ResetPasswordController;
use App\Modules\Auth\Requests\ResetPasswordRequest;

class ResetPasswordController extends Controller
{
    protected $resetPasswordController;

    public function __construct(V1ResetPasswordController $resetPasswordController)
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
