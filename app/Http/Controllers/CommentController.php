<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Comment = Comment::latest()->paginate(10);
        $Comment = Comment::with('owner:id,name,lname')->latest()->get();

        if($Comment->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Comment
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found'
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreCommentRequest $request)
    {
        $Comment = Comment::create($request->all());

        if($Comment!=null){
            $response = [
                'value' => true,
                'data' => $Comment
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Comment = Comment::where('patient_id', $id)
        ->with('owner:id,name,lname')->latest()->get();

        if($Comment->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Comment
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, $id)
    {
        $Comment = Comment::where('id', $id)->first();

        if($Comment!=null){
            $Comment->update($request->all());
            $response = [
                'value' => true,
                'data' => $Comment,
                'message' => 'Comment Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Comment = Comment::where('id', $id)->first();

        if($Comment!=null){
            DB::table('Comments')->where('id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Comment Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found'
            ];
            return response($response, 404);
        }
    }
}
