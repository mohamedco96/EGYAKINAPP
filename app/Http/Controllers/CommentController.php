<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Patients;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comments = Comment::with('doctor:id,name,lname,workingplace')->latest()->get();

        if ($comments->isEmpty()) {
            $response = [
                'value' => false,
                'message' => 'No comments were found',
            ];

            return response()->json($response, 404);
        }

        $response = [
            'value' => true,
            'data' => $comments,
        ];

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCommentRequest $request)
    {
        $patient = Patients::where('id', $request->patient_id)->first();

        if (!$patient) {
            $response = [
                'value' => false,
                'message' => 'Patient not found',
            ];
            return response()->json($response, 404);
        }

        $comment = Comment::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'content' => $request->content,
        ]);

        // Retrieve the patient's doctor ID
        $patientDoctorId = Patients::where('id', $request->patient_id)->value('doctor_id');

        // Check if the authenticated user is the patient's doctor
        if ($patientDoctorId !== Auth::id()) {
            // Send notification to the patient's doctor
            Notification::create([
                'content' => 'New comment was created',
                'read' => false,
                'type' => 'Comment',
                'patient_id' => $request->patient_id,
                'doctor_id' => $patientDoctorId,
            ]);
        }


        if ($comment !== null) {
            $response = [
                'value' => true,
                'message' => 'Comment created successfully',
            ];

            Log::info('New comment created', [
                'comment_id' => $comment->id,
                'patient_id' => $request->patient_id,
                'doctor_id' => $patientDoctorId,
            ]);

            return response()->json($response, 200);
        }

        $response = [
            'value' => false,
            'message' => 'Failed to create comment',
        ];

        return response()->json($response, 500);
    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $comments = Comment::where('patient_id', $patient_id)
            ->select('id', 'doctor_id', 'content', 'updated_at')
            ->with('doctor:id,name,lname,workingplace')
            ->get();

        $response = [
            'value' => true,
            'data' => $comments,
        ];

        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCommentRequest $request, $id)
    {
        $comment = Comment::find($id);

        if ($comment !== null) {
            $comment->update($request->all());

            $response = [
                'value' => true,
                'data' => $comment,
                'message' => 'Comment updated successfully',
            ];

            Log::info('Comment updated', [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'doctor_id' => Auth::id(),
            ]);

            return response()->json($response, 200);
        }

        $response = [
            'value' => false,
            'message' => 'Comment not found',
        ];

        return response()->json($response, 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);

        if ($comment !== null) {
            $comment->delete();

            $response = [
                'value' => true,
                'message' => 'Comment deleted successfully',
            ];

            Log::info('Comment deleted', [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'doctor_id' => Auth::id(),
            ]);

            return response()->json($response, 200);
        }

        $response = [
            'value' => false,
            'message' => 'Comment not found',
        ];

        return response()->json($response, 404);
    }
}
