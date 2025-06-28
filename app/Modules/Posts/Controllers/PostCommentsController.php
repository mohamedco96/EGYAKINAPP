<?php

namespace App\Modules\Posts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Requests\StorePostCommentsRequest;
use App\Modules\Posts\Requests\UpdatePostCommentsRequest;
use App\Modules\Posts\Services\PostCommentService;
use App\Modules\Posts\Models\PostComments;

class PostCommentsController extends Controller
{
    protected $postCommentService;

    public function __construct(PostCommentService $postCommentService)
    {
        $this->postCommentService = $postCommentService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // This method can be implemented if needed to list all comments
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // This method can be implemented if needed for web interface
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostCommentsRequest $request)
    {
        $validatedData = $request->validated();
        $postId = $request->post_id;
        
        $result = $this->postCommentService->createComment($validatedData, $postId);
        
        $statusCode = $result['value'] ? 200 : 404;
        
        return response($result, $statusCode);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $result = $this->postCommentService->getCommentsByPostId($id);
        
        return response($result, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PostComments $postComments)
    {
        // This method can be implemented if needed for web interface
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostCommentsRequest $request, PostComments $postComments)
    {
        $validatedData = $request->validated();
        
        $result = $this->postCommentService->updateComment($postComments, $validatedData);
        
        return response($result, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = $this->postCommentService->deleteComment($id);
        
        $statusCode = $result['value'] ? 200 : 404;
        
        return response($result, $statusCode);
    }
}
