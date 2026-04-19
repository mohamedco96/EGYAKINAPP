<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Api\V1\QuestionsController as V1QuestionsController;
use App\Http\Controllers\Controller;
use App\Models\SectionsInfo;
use App\Modules\Questions\Requests\StoreQuestionsRequest;
use App\Modules\Questions\Requests\UpdateQuestionsRequest;

class QuestionsController extends Controller
{
    protected $questionsController;

    public function __construct(V1QuestionsController $questionsController)
    {
        $this->questionsController = $questionsController;
    }

    public function index()
    {
        return $this->questionsController->index();
    }

    public function store(StoreQuestionsRequest $request)
    {
        return $this->questionsController->store($request);
    }

    public function show($section_id)
    {
        $response = $this->questionsController->show($section_id);

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

    public function ShowQuestitionsAnswars($section_id, $patient_id)
    {
        return $this->questionsController->ShowQuestitionsAnswars($section_id, $patient_id);
    }

    public function update(UpdateQuestionsRequest $request, $id)
    {
        return $this->questionsController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->questionsController->destroy($id);
    }
}
