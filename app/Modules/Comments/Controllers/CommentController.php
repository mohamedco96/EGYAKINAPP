<?php

namespace App\Modules\Comments\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Comments\Requests\StoreCommentRequest;
use App\Modules\Comments\Requests\UpdateCommentRequest;
use App\Modules\Comments\Services\CommentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $result = $this->commentService->getAllComments();

            return response()->json($result['data'], $result['status_code']);
        } catch (Exception $e) {
            Log::error('Error retrieving comments: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while retrieving comments',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        try {
            $result = $this->commentService->createComment($request->validated());

            return response()->json($result['data'], $result['status_code']);
        } catch (Exception $e) {
            Log::error('Error creating comment: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while creating the comment',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $patient_id): JsonResponse
    {
        try {
            $result = $this->commentService->getCommentsByPatient($patient_id);

            return response()->json($result['data'], $result['status_code']);
        } catch (Exception $e) {
            Log::error("Error retrieving comments for patient ID {$patient_id}: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while retrieving patient comments',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->commentService->updateComment($id, $request->validated());

            return response()->json($result['data'], $result['status_code']);
        } catch (Exception $e) {
            Log::error("Error updating comment ID {$id}: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while updating the comment',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->commentService->deleteComment($id);

            return response()->json($result['data'], $result['status_code']);
        } catch (Exception $e) {
            Log::error("Error deleting comment ID {$id}: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while deleting the comment',
            ], 500);
        }
    }
}
