<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Models\Score;
use App\Models\ScoreHistory;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$complaint = Complaint::latest()->paginate(10);
        $complaint = Complaint::latest()->get();

        if ($complaint != null) {
            $response = [
                'value' => true,
                'data' => $complaint,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
            ];

            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreComplaintRequest $request)
    {
        $complaint = Complaint::create($request->all());

        if ($complaint != null) {
            $response = [
                'value' => true,
                'data' => $complaint,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $complaint = Complaint::where('patient_id', $id)->first();

        if ($complaint != null) {
            $response = [
                'value' => true,
                'data' => $complaint,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComplaintRequest $request, $id)
    {
        $complaint = Complaint::where('patient_id', $id)->first();

        if ($complaint != null) {
            $complaint->update($request->all());

            DB::table('sections')->where('patient_id', $id)->update(['section_2' => true]);

            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();

            $incrementAmount = 5; // Example increment amount
            $action = 'Update Complaint Section'; // Example action

            if ($score) {
                $score->increment('score', $incrementAmount); // Increase the score
            } else {
                Score::create([
                    'doctor_id' => $doctorId,
                    'score' => $incrementAmount,
                ]);
            }

            ScoreHistory::create([
                'doctor_id' => $doctorId,
                'score' => $incrementAmount,
                'action' => $action,
                'timestamp' => now(),
            ]);

            $response = [
                'value' => true,
                'data' => $complaint,
                'message' => 'Complaint Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $complaint = Complaint::where('patient_id', $id)->first();

        if ($complaint != null) {
            DB::table('complaints')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Complaint Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found',
            ];

            return response($response, 404);
        }
    }
}
