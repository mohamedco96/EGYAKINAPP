<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PatientHistory;
use App\Models\Section;
use App\Models\Complaint;
use App\Models\Cause;
use App\Models\Risk;
use App\Models\Assessment;
use App\Models\Examination;
use App\Services\Auth\AuthService;
use App\Services\User\UserService;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    protected $authService;
    protected $userService;

    public function __construct(AuthService $authService, UserService $userService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $result = $this->authService->register($request->validated());
            return response()->json($result, 201);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email')
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Registration failed'
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());
            return response()->json($result, $result['value'] ? 200 : 401);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email')
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Login failed'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $result = $this->authService->logout($request->user());
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $result = $this->userService->updateUser(Auth::user(), $request->all());
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No User was found'
            ], 404);
        }
    }

    public function updateUserById(Request $request, $id)
    {
        try {
            $result = $this->userService->updateUserById($id, $request->all());
            return response()->json($result, $result['value'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to update user'
            ], 500);
        }
    }

    public function uploadProfileImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if (!$request->hasFile('image')) {
                throw new \Exception('No image file provided');
            }

            $result = $this->userService->uploadProfileImage(Auth::user(), $request->file('image'));
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Profile image upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'value' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function uploadSyndicateCard(Request $request)
    {
        try {
            $request->validate([
                'syndicate_card' => 'required|image|mimes:jpeg,png,jpg,gif'
            ]);

            if (!$request->hasFile('syndicate_card')) {
                throw new \Exception('No image file provided');
            }

            $result = $this->userService->uploadSyndicateCard(Auth::user(), $request->file('syndicate_card'));
            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Syndicate card upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'value' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function index()
    {
        $users = User::all();

        if ($users->isNotEmpty()) {
            return response()->json([
                'value' => true,
                'data' => $users
            ], 200);
        }

        return response()->json([
            'value' => false,
            'message' => 'No user was found'
        ], 404);
    }

    public function show($id)
    {
        try {
            $user = User::with([
                'patients' => function($q) {
                    $q->select('id', 'doctor_id');
                },
                'score:id,doctor_id,score',
                'posts:id,doctor_id',
                'saves:id,doctor_id'
            ])
            ->select('id', 'name', 'lname', 'image', 'email', 'specialty', 'workingplace')
            ->findOrFail($id);

            $patientCount = $user->patients()->count();
            $postsCount = $user->posts()->count();
            $savedPostsCount = $user->saves()->count();
            $scoreValue = optional($user->score)->score ?? 0;
            $imageUrl = $user->image ? config('app.url') . '/storage/' . $user->image : null;

            return response()->json([
                'value' => true,
                'patient_count' => strval($patientCount),
                'score_value' => strval($scoreValue),
                'posts_count' => strval($postsCount),
                'saved_posts_count' => strval($savedPostsCount),
                'image' => $imageUrl,
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching user profile', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No user was found'
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            DB::beginTransaction();
            try {
                $user->delete();
                PatientHistory::where('doctor_id', $id)->delete();
                Section::where('doctor_id', $id)->delete();
                Complaint::where('doctor_id', $id)->delete();
                Cause::where('doctor_id', $id)->delete();
                Risk::where('doctor_id', $id)->delete();
                Assessment::where('doctor_id', $id)->delete();
                Examination::where('doctor_id', $id)->delete();
                
                DB::commit();

                return response()->json([
                    'value' => true,
                    'message' => 'User Deleted Successfully'
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'No User was found'
            ], 404);
        }
    }
} 