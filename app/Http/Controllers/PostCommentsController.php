<?php

namespace App\Http\Controllers;

use App\Models\PostComments;
use App\Models\Posts;
use App\Http\Requests\StorePostCommentsRequest;
use App\Http\Requests\UpdatePostCommentsRequest;
use Illuminate\Support\Facades\Auth;

class PostCommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostCommentsRequest $request)
    {
        // Validate the input
        $validatedData = $request->validated();

        // Create the comment
        $comment = new PostComments([
            'content' => $validatedData['content'],
        ]);

        // Associate the comment with the current user and post
        $user = Auth::user();
        $post = Posts::find($request->post_id);
        $user->postcomments()->save($comment);
        $post->postcomments()->save($comment);

        if($post!=null){
            $response = [
                'value' => true,
                'data' => $comment
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No post was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PostComments $postComments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PostComments $postComments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostCommentsRequest $request, PostComments $postComments)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostComments $postComments)
    {
        //
    }
}
