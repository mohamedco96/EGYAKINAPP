<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Achievements\Controllers\AchievementController as ModuleAchievementController;
use App\Modules\Achievements\Requests\StoreAchievementRequest;
use App\Modules\Achievements\Requests\UpdateAchievementRequest;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    protected $achievementController;

    public function __construct(ModuleAchievementController $achievementController)
    {
        $this->achievementController = $achievementController;
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

    public function getUserAchievements($user)
    {
        // Resolve the user from the route parameter (could be ID or other identifier)
        $userModel = User::find($user);

        // Return empty array if user not found (same format as when user has no achievements)
        if (!$userModel) {
            return response()->json([], 200);
        }

        return $this->achievementController->getUserAchievements($userModel);
    }

    public function checkAndAssignAchievementsForAllUsers(Request $request)
    {
        return $this->achievementController->checkAndAssignAchievementsForAllUsers($request);
    }
}
