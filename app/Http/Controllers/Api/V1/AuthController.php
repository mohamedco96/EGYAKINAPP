<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Controllers\AuthController as ModuleAuthController;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\UpdateProfileRequest;
use App\Modules\Auth\Requests\UploadProfileImageRequest;
use App\Modules\Auth\Requests\UploadSyndicateCardRequest;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $authController;

    public function __construct(ModuleAuthController $authController)
    {
        $this->authController = $authController;
    }

    public function register(RegisterRequest $request)
    {
        return $this->authController->register($request);
    }

    public function login(LoginRequest $request)
    {
        return $this->authController->login($request);
    }

    public function logout(Request $request)
    {
        return $this->authController->logout($request);
    }

    public function index()
    {
        return $this->authController->index();
    }

    public function show($id)
    {
        return $this->authController->show($id);
    }

    public function showAnotherProfile($id)
    {
        return $this->authController->showAnotherProfile($id);
    }

    public function doctorProfileGetPatients($id)
    {
        return $this->authController->doctorProfileGetPatients($id);
    }

    public function doctorProfileGetScoreHistory($id)
    {
        return $this->authController->doctorProfileGetScoreHistory($id);
    }

    public function update(UpdateProfileRequest $request)
    {
        return $this->authController->update($request);
    }

    public function updateUserById(UpdateProfileRequest $request, $id)
    {
        return $this->authController->updateUserById($request, $id);
    }

    public function destroy($id)
    {
        return $this->authController->destroy($id);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->authController->changePassword($request);
    }

    public function uploadProfileImage(UploadProfileImageRequest $request)
    {
        return $this->authController->uploadProfileImage($request);
    }

    public function uploadSyndicateCard(UploadSyndicateCardRequest $request)
    {
        return $this->authController->uploadSyndicateCard($request);
    }

    public function roletest(Request $request)
    {
        return $this->authController->roletest($request);
    }
}
