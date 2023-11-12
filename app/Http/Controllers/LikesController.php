<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLikesRequest;
use App\Http\Requests\UpdateLikesRequest;
use App\Models\Likes;
use App\Models\PatientHistory;
use Illuminate\Support\Facades\Auth;

class LikesController extends Controller
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
    public function like(StoreLikesRequest $request)
    {
        $patient = PatientHistory::where('id', $request->patient_id)->first();
        $liked = Likes::where('patient_id', $request->patient_id)
            ->Where('doctor_id', Auth::id())
            ->Where('comment_id', $request->comment_id)
            ->Where('liked', true)
            ->first();

        $unliked = Likes::where('patient_id', $request->patient_id)
            ->Where('doctor_id', Auth::id())
            ->Where('comment_id', $request->comment_id)
            ->Where('liked', false)
            ->first();

        if ($patient != null) {

            if ($liked != null) {
                $response = [
                    'value' => false,
                    'message' => 'comment already liked',
                ];
            }

            if ($unliked != null) {

                Likes::where('patient_id', '=', $request->patient_id)
                    ->Where('doctor_id', Auth::id())
                    ->Where('comment_id', $request->comment_id)
                    ->Where('liked', false)
                    ->update(['liked' => true]);
                $response = [
                    'value' => true,
                    'message' => 'comment liked Successfully',
                ];
            }
            if ($liked == null && $unliked == null) {
                $like = Likes::create([
                    'doctor_id' => Auth::id(),
                    'patient_id' => $request->patient_id,
                    'comment_id' => $request->comment_id,
                    'liked' => true,
                ]);
                $response = [
                    'value' => true,
                    'message' => 'comment liked Successfully',
                ];
            }

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No patient was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function unlike(StoreLikesRequest $request)
    {
        $patient = PatientHistory::where('id', $request->patient_id)->first();
        $liked = Likes::where('patient_id', $request->patient_id)
            ->Where('doctor_id', Auth::id())
            ->Where('comment_id', $request->comment_id)
            ->Where('liked', true)
            ->first();

        $unliked = Likes::where('patient_id', $request->patient_id)
            ->Where('doctor_id', Auth::id())
            ->Where('comment_id', $request->comment_id)
            ->Where('liked', false)
            ->first();

        if ($patient != null) {

            if ($liked != null) {
                Likes::where('patient_id', '=', $request->patient_id)
                    ->Where('doctor_id', Auth::id())
                    ->Where('comment_id', $request->comment_id)
                    ->Where('liked', true)
                    ->update(['liked' => false]);

                $response = [
                    'value' => true,
                    'message' => 'comment unliked Successfully',
                ];
            }
            if ($unliked != null) {
                $response = [
                    'value' => false,
                    'message' => 'comment already unliked',
                ];
            }
            if ($liked == null && $unliked == null) {
                $like = Likes::create([
                    'doctor_id' => Auth::id(),
                    'patient_id' => $request->patient_id,
                    'comment_id' => $request->comment_id,
                    'liked' => false,
                ]);
                $response = [
                    'value' => true,
                    'message' => 'comment unliked Successfully',
                ];
            }

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No patient was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Likes $likes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Likes $likes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLikesRequest $request, Likes $likes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Likes $likes)
    {
        //
    }
}
