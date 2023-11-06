<?php

namespace App\Http\Controllers;

use App\Models\Posts;
use App\Http\Requests\StorePostsRequest;
use App\Http\Requests\UpdatePostsRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $post = Posts::
        select('id','title', 'image', 'content', 'hidden', 'doctor_id' ,'updated_at')
        ->with('doctor:id,name,lname')  
        ->get();

        if($post!=null){
            $response = [
                'value' => true,
                'data' => $post
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
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostsRequest $request)
    {
        // Validate the input
        $validatedData = $request->validated();

        // Handle file upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('images', 'public');
            $imagePath = Storage::disk('public')->url($imagePath);
        }

        // Create the post
        $post = new Posts([
            'title' => $validatedData['title'],
            'image' => $imagePath,
            'content' => $validatedData['content'],
        ]);

        // Associate the post with the current user
        $user = Auth::user();
        $user->posts()->save($post);
        if($post!=null){
            $response = [
                'value' => true,
                'data' => $post
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No user was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //$post = Posts::find($id);

        // Load the post's comments
        //$post->load('postcomments.doctor');

        $post = Posts::where('id', $id)
        ->select('id','title', 'image', 'content', 'hidden', 'doctor_id' ,'updated_at')
        ->with('doctor:id,name,lname')  
        ->with('postcomments:id,content,doctor_id,post_id')           
        ->get();

        if($post!=null){
            $response = [
                'value' => true,
                'data' => $post
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
     * Show the form for editing the specified resource.
     */
    public function edit(Posts $posts)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostsRequest $request, Posts $posts)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Posts $posts)
    {
        //
    }
}
