<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Recommendations\Controllers\RecommendationController as ModuleRecommendationController;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    protected $recommendationController;

    public function __construct(ModuleRecommendationController $recommendationController)
    {
        $this->recommendationController = $recommendationController;
    }

    public function index($patient_id)
    {
        return $this->recommendationController->index($patient_id);
    }

    public function store(Request $request, $patient_id)
    {
        return $this->recommendationController->store($request, $patient_id);
    }

    public function update(Request $request, $patient_id)
    {
        return $this->recommendationController->update($request, $patient_id);
    }

    public function destroy($patient_id)
    {
        return $this->recommendationController->destroy($patient_id);
    }
}
