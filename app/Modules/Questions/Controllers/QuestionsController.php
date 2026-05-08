<?php

namespace App\Modules\Questions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Questions\Requests\StoreQuestionsRequest;
use App\Modules\Questions\Requests\UpdateQuestionsRequest;
use App\Modules\Questions\Services\QuestionService;
use Illuminate\Http\JsonResponse;

class QuestionsController extends Controller
{
    protected $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $result = $this->questionService->getAllQuestions();

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionsRequest $request): JsonResponse
    {
        $result = $this->questionService->storeQuestion($request->validated());

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $sectionId): JsonResponse
    {
        $result = $this->questionService->getQuestionsBySection($sectionId);

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Display questions and answers for a specific section and patient.
     */
    public function ShowQuestitionsAnswars(int $sectionId, int $patientId): JsonResponse
    {
        $result = $this->questionService->getQuestionsWithAnswers($sectionId, $patientId);

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionsRequest $request, int $id): JsonResponse
    {
        $result = $this->questionService->updateQuestion($id, $request->validated());

        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $result = $this->questionService->deleteQuestion($id);

        return response()->json($result['data'], $result['status_code']);
    }
}
