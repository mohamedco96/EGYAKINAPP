<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\ResetPasswordController as ModuleResetPasswordController;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    protected $resetPasswordController;

    public function __construct(ModuleResetPasswordController $resetPasswordController)
    {
        $this->resetPasswordController = $resetPasswordController;
    }

    public function resetpasswordverification(Request $request)
    {
        return $this->resetPasswordController->resetpasswordverification($request);
    }

    public function resetpassword(Request $request)
    {
        return $this->resetPasswordController->resetpassword($request);
    }
}
