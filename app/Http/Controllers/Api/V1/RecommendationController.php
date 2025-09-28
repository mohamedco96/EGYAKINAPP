<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Recommendations\Controllers\RecommendationController as ModuleRecommendationController;
use App\Modules\Recommendations\Requests\DeleteRecommendationRequest;
use App\Modules\Recommendations\Requests\StoreRecommendationRequest;
use App\Modules\Recommendations\Requests\UpdateRecommendationRequest;

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

    public function store(StoreRecommendationRequest $request, $patient_id)
    {
        return $this->recommendationController->store($request, $patient_id);
    }

    public function update(UpdateRecommendationRequest $request, $patient_id)
    {
        return $this->recommendationController->update($request, $patient_id);
    }

    public function destroy(DeleteRecommendationRequest $request, $patient_id)
    {
        return $this->recommendationController->destroy($request, $patient_id);
    }
}
