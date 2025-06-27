<?php

namespace App\Modules\Achievements\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Achievements\Services\AchievementService;
use App\Modules\Achievements\Requests\StoreAchievementRequest;
use App\Modules\Achievements\Requests\UpdateAchievementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AchievementController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    /**
     * Display a listing of achievements.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $result = $this->achievementService->getAllAchievements();
            $statusCode = $result['value'] ? 200 : 404;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@index', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while retrieving achievements'
            ], 500);
        }
    }

    /**
     * Store a newly created achievement.
     *
     * @param \App\Modules\Achievements\Requests\StoreAchievementRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreAchievementRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Add the uploaded file to the data if present
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image');
            }
            
            $result = $this->achievementService->createAchievement($data);
            $statusCode = $result['value'] ? 201 : 400;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@store', ['error' => $e->getMessage()]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while creating the achievement'
            ], 500);
        }
    }

    /**
     * Display the specified achievement.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->achievementService->getAchievementById($id);
            $statusCode = $result['value'] ? 200 : 404;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@show', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while retrieving the achievement'
            ], 500);
        }
    }

    /**
     * Update the specified achievement.
     *
     * @param \App\Modules\Achievements\Requests\UpdateAchievementRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateAchievementRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Add the uploaded file to the data if present
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image');
            }
            
            $result = $this->achievementService->updateAchievement($id, $data);
            $statusCode = $result['value'] ? 200 : 404;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@update', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while updating the achievement'
            ], 500);
        }
    }

    /**
     * Remove the specified achievement.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->achievementService->deleteAchievement($id);
            $statusCode = $result['value'] ? 200 : 404;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@destroy', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while deleting the achievement'
            ], 500);
        }
    }

    /**
     * Create a new achievement (legacy method name for backward compatibility).
     *
     * @param \App\Modules\Achievements\Requests\StoreAchievementRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createAchievement(StoreAchievementRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * List all achievements (legacy method name for backward compatibility).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listAchievements(): JsonResponse
    {
        return $this->index();
    }

    /**
     * Check and assign achievements to all users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAndAssignAchievementsForAllUsers(): JsonResponse
    {
        try {
            $result = $this->achievementService->checkAndAssignAchievementsForAllUsers();
            $statusCode = $result['value'] ? 200 : 500;
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@checkAndAssignAchievementsForAllUsers', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while processing achievements for all users'
            ], 500);
        }
    }

    /**
     * Get achievements for a specific user.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserAchievements(User $user): JsonResponse
    {
        try {
            $result = $this->achievementService->getUserAchievements($user);
            $statusCode = $result['value'] ? 200 : 404;
            
            // Return only the data for backward compatibility
            if ($result['value']) {
                return response()->json($result['data'], $statusCode);
            }
            
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            Log::error('Error in AchievementController@getUserAchievements', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while retrieving user achievements'
            ], 500);
        }
    }
}
