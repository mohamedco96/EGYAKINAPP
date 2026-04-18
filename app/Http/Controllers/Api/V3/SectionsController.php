<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\V1\SectionsController as V1SectionsController;
use App\Http\Controllers\Controller;
use App\Models\SectionsInfo;
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
        $response = $this->sectionsController->showQuestionsAnswers($section_id, $patient_id);

        $payload = $response->getData(true);

        $section = SectionsInfo::find((int) $section_id);
        $aiMode  = $section?->ai_mode ?? null;
        $aiHint  = $section?->ai_hint ?? null;

        $payload = array_merge(
            ['value'   => $payload['value']],
            ['ai_mode' => $aiMode],
            ['ai_hint' => $aiHint],
            array_diff_key($payload, ['value' => null])
        );

        return response()->json($payload, $response->status());
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
