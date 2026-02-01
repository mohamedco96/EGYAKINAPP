<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Controllers\Api\V1\AchievementController as V1AchievementController;
use App\Modules\Achievements\Controllers\AchievementController as ModuleAchievementController;
use App\Modules\Achievements\Requests\StoreAchievementRequest;
use App\Modules\Achievements\Requests\UpdateAchievementRequest;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    protected $achievementController;

    protected $moduleAchievementController;

    public function __construct(V1AchievementController $achievementController, ModuleAchievementController $moduleAchievementController)
    {
        $this->achievementController = $achievementController;
        $this->moduleAchievementController = $moduleAchievementController;
    }

    public function index()
    {
        return $this->achievementController->index();
    }

    public function store(StoreAchievementRequest $request)
    {
        return $this->achievementController->store($request);
    }

    public function show($id)
    {
        return $this->achievementController->show($id);
    }

    public function update(UpdateAchievementRequest $request, $id)
    {
        return $this->achievementController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->achievementController->destroy($id);
    }

    public function createAchievement(Request $request)
    {
        return $this->achievementController->createAchievement($request);
    }

    public function listAchievements()
    {
        return $this->achievementController->listAchievements();
    }

    public function getUserAchievements($userId)
    {
        // Handle case where $userId might already be a User model or Collection from route binding
        if ($userId instanceof User) {
            return $this->moduleAchievementController->getUserAchievements($userId);
        }

        if ($userId instanceof \Illuminate\Database\Eloquent\Collection) {
            $userId = $userId->first()?->id;
        }

        $userModel = User::find($userId);

        // Return empty array if user not found (same format as when user has no achievements)
        if (!$userModel) {
            return response()->json([], 200);
        }

        return $this->moduleAchievementController->getUserAchievements($userModel);
    }

    public function checkAndAssignAchievementsForAllUsers(Request $request)
    {
        return $this->achievementController->checkAndAssignAchievementsForAllUsers($request);
    }
}
