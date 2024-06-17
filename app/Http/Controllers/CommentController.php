<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\AppNotification;
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
        $patient = Patients::find($request->patient_id);
        $doctorID = Auth::id();

        if (!$patient) {
            return response()->json([
                'value' => false,
                'message' => 'Patient not found',
            ], 404);
        }

        $comment = Comment::create([
            'doctor_id' => $doctorID,
            'patient_id' => $request->patient_id,
            'content' => $request->content,
        ]);

        if (!$comment) {
            return response()->json([
                'value' => false,
                'message' => 'Failed to create comment',
            ], 500);
        }

        // Retrieve the patient's doctor ID and cast to integer
        $patientDoctorId = (int) $patient->doctor_id;

        // Log the doctor IDs for debugging
        Log::debug('Authenticated Doctor ID:', ['doctor_id' => $doctorID]);
        Log::debug('Patient Doctor ID:', ['patient_doctor_id' => $patientDoctorId]);

        // Check if the authenticated user is not the patient's doctor
        if ($patientDoctorId !== $doctorID) {
            // Send notification to the patient's doctor
            AppNotification::create([
                'content' => 'New comment was created',
                'read' => false,
                'type' => 'Comment',
                'patient_id' => $request->patient_id,
                'doctor_id' => $patientDoctorId,
            ]);
        } else {
            // Log that no notification was sent
            Log::debug('No notification sent as the authenticated doctor is the same as the patient\'s doctor.');
        }

        Log::info('New comment created', [
            'comment_id' => $comment->id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $doctorID,
        ]);

        return response()->json([
            'value' => true,
            'message' => 'Comment created successfully',
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $comments = Comment::where('patient_id', $patient_id)
            ->select('id', 'doctor_id', 'content', 'updated_at')
            ->with('doctor:id,name,lname,workingplace,image')
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
