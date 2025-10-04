<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\SectionsController as V1SectionsController;
use App\Modules\Sections\Requests\UpdateFinalSubmitRequest;

class SectionsController extends Controller
{
    protected $sectionsController;

    public function __construct(V1SectionsController $sectionsController)
    {
        $this->sectionsController = $sectionsController;
    }

    public function showQuestionsAnswers($section_id, $patient_id)
    {
        return $this->sectionsController->showQuestionsAnswers($section_id, $patient_id);
    }

    public function updateFinalSubmit(UpdateFinalSubmitRequest $request, $patient_id)
    {
        return $this->sectionsController->updateFinalSubmit($request, $patient_id);
    }

    public function showSections($patient_id)
    {
        return $this->sectionsController->showSectionsV1($patient_id);
    }
}
