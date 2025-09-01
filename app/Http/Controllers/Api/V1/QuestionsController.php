<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Questions\Controllers\QuestionsController as ModuleQuestionsController;
use App\Modules\Questions\Requests\StoreQuestionsRequest;
use App\Modules\Questions\Requests\UpdateQuestionsRequest;

class QuestionsController extends Controller
{
    protected $questionsController;

    public function __construct(ModuleQuestionsController $questionsController)
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
        return $this->questionsController->show($section_id);
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
