<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Models\Assessment;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Assessment = Assessment::latest()->paginate(10);
        $Assessment = Assessment::latest()->get();

        if ($Assessment != null) {
            $response = [
                'value' => true,
                'data' => $Assessment,
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
    public function store(StoreAssessmentRequest $request)
    {
        $Assessment = Assessment::create($request->all());

        if ($Assessment != null) {
            $response = [
                'value' => true,
                'data' => $Assessment,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Assessment was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Assessment = Assessment::where('patient_id', $id)->first();

        if ($Assessment != null) {
            $response = [
                'value' => true,
                'data' => $Assessment,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Assessment was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssessmentRequest $request, $id)
    {
        $Assessment = Assessment::where('patient_id', $id)->first();

        if ($Assessment != null) {
            $Assessment->update($request->all());

            DB::table('sections')->where('patient_id', $id)->update(['section_5' => true]);
            /*
            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();

            $incrementAmount = 5; // Example increment amount
            $action = 'Update Assessment of the patient Section'; // Example action

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
            ]);*/

            $response = [
                'value' => true,
                'data' => $Assessment,
                'message' => 'Assessment Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Assessment was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Assessment = Assessment::where('patient_id', $id)->first();

        if ($Assessment != null) {
            DB::table('assessments')->where('patient_id', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'Assessment Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Assessment was found',
            ];

            return response($response, 404);
        }
    }
}
