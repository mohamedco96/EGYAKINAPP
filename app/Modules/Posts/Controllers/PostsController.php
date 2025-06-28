<?php

namespace App\Modules\Posts\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Requests\StorePostsRequest;
use App\Modules\Posts\Requests\UpdatePostsRequest;
use App\Modules\Posts\Services\PostService;
use App\Modules\Posts\Models\Posts;

class PostsController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result = $this->postService->getAllPosts();
        
        $statusCode = $result['value'] ? 200 : 404;
        
        return response($result, $statusCode);
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
    public function store(StorePostsRequest $request)
    {
        $validatedData = $request->validated();
        $imageFile = $request->hasFile('image') ? $request->file('image') : null;
        
        $result = $this->postService->createPost($validatedData, $imageFile);
        
        $statusCode = $result['value'] ? 200 : 404;
        
        return response($result, $statusCode);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $result = $this->postService->getPostById($id);
        
        return response($result, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Posts $posts)
    {
        // This method can be implemented if needed for web interface
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostsRequest $request, Posts $posts)
    {
        $validatedData = $request->validated();
        $imageFile = $request->hasFile('image') ? $request->file('image') : null;
        
        $result = $this->postService->updatePost($posts, $validatedData, $imageFile);
        
        return response($result, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Posts $posts)
    {
        $result = $this->postService->deletePost($posts);
        
        return response($result, 200);
    }
}
