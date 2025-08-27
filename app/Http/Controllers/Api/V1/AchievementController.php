<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Achievements\Controllers\AchievementController as ModuleAchievementController;
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

    public function store(Request $request)
    {
        return $this->achievementController->store($request);
    }

    public function show($id)
    {
        return $this->achievementController->show($id);
    }

    public function update(Request $request, $id)
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
        return $this->achievementController->getUserAchievements($user);
    }

    public function checkAndAssignAchievementsForAllUsers(Request $request)
    {
        return $this->achievementController->checkAndAssignAchievementsForAllUsers($request);
    }
}
