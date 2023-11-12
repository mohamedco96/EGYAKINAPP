<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Comment = Comment::with('doctor:id,name,lname,workingplace')->latest()->get();

        if ($Comment->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Comment,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found',
            ];

            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreCommentRequest $request)
    {
        $Comment = Comment::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'content' => $request->content,
        ]);

        if ($Comment != null) {
            $response = [
                'value' => true,
                'message' => 'Comment Created Successfully',
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $Comment = Comment::where('patient_id', $patient_id)
            ->select('id', 'doctor_id', 'content', 'updated_at')
            ->with('doctor:id,name,lname,workingplace')->get();

        if ($Comment->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Comment,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found',
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

        if ($Comment != null) {
            $Comment->update($request->all());
            $response = [
                'value' => true,
                'data' => $Comment,
                'message' => 'Comment Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found',
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

        if ($Comment != null) {
            DB::table('comments')->where('id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Comment Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Comment was found',
            ];

            return response($response, 404);
        }
    }
}
