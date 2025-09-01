<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Comments\Controllers\CommentController as ModuleCommentController;
use App\Modules\Comments\Requests\StoreCommentRequest;
use App\Modules\Comments\Requests\UpdateCommentRequest;

class CommentController extends Controller
{
    protected $commentController;

    public function __construct(ModuleCommentController $commentController)
    {
        $this->commentController = $commentController;
    }

    public function index()
    {
        return $this->commentController->index();
    }

    public function store(StoreCommentRequest $request)
    {
        return $this->commentController->store($request);
    }

    public function show($patient_id)
    {
        return $this->commentController->show($patient_id);
    }

    public function update(UpdateCommentRequest $request, $patient_id)
    {
        return $this->commentController->update($request, $patient_id);
    }

    public function destroy($patient_id)
    {
        return $this->commentController->destroy($patient_id);
    }
}
