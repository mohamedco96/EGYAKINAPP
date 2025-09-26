<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ChangePasswordRequest;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Requests\UpdateProfileRequest;
use App\Modules\Auth\Requests\UploadProfileImageRequest;
use App\Modules\Auth\Requests\UploadSyndicateCardRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function decryptedPassword()
    {
        $decryptedPassword = $this->authService->decryptPassword('eyJpdiI6IkNFbWE3UnA2MEFmTnRUalUzY2hJREE9PSIsInZhbHVlIjoibVpRUDhidkMxTWtpM2pNb0lTYlFRMUc4WVJyRnUvemZQRURWTzZ0Ukhaaz0iLCJtYWMiOiJhN2JlNTY2NzZjMjlkNWNmOTg1MThlMjA4NWNjNjcyNWQxMWUyNWFkZjg4NDAzMGJhYTZiMTYzODExNjFjODM4IiwidGFnIjoiIn0=');

        dd($decryptedPassword);
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'value' => false,
                'message' => array_values($e->errors())[0][0],
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.registration_failed'),
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (ValidationException $e) {
            return response()->json([
                'value' => false,
                'message' => array_values($e->errors())[0][0],
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Login failed',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $result = $this->authService->logout();
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Logout failed',
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $result = $this->authService->changePassword($request->validated());
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (ValidationException $e) {
            return response()->json([
                'value' => false,
                'message' => array_values($e->errors())[0][0],
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Password change failed',
            ], 500);
        }
    }

    public function uploadProfileImage(UploadProfileImageRequest $request)
    {
        try {
            if (! $request->hasFile('image')) {
                throw new \Exception('No image file provided');
            }

            $result = $this->authService->uploadProfileImage($request->file('image'));
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Profile image upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function uploadSyndicateCard(UploadSyndicateCardRequest $request)
    {
        try {
            if (! $request->hasFile('syndicate_card')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please choose an image file.',
                ], 400);
            }

            $result = $this->authService->uploadSyndicateCard($request->file('syndicate_card'));
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Syndicate card upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(UpdateProfileRequest $request)
    {
        try {
            $result = $this->authService->updateProfile($request->validated());
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (ValidationException $e) {
            return response()->json([
                'value' => false,
                'message' => array_values($e->errors())[0][0],
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'User update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateUserById(Request $request, $id)
    {
        try {
            $result = $this->authService->updateUserById($id, $request->all());
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('User update by ID failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Update failed',
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $result = $this->authService->getAllUsers();
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error retrieving users', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve users',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $result = $this->authService->getUserProfile($id);
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No user was found',
            ], 404);
        }
    }

    public function showAnotherProfile($id)
    {
        try {
            $result = $this->authService->getAnotherUserProfile($id);
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error fetching another user profile', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No user was found',
            ], 404);
        }
    }

    public function doctorProfileGetPatients($id)
    {
        try {
            $result = $this->authService->getDoctorPatients($id);
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error retrieving doctor patients', [
                'doctor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve patients',
            ], 500);
        }
    }

    public function doctorProfileGetScoreHistory($id)
    {
        try {
            $result = $this->authService->getDoctorScoreHistory($id);
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error retrieving doctor score history', [
                'doctor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to retrieve score history',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $result = $this->authService->deleteUser($id);
            $statusCode = $result['status_code'] ?? 200;
            unset($result['status_code']);

            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'User deletion failed',
            ], 500);
        }
    }
}
