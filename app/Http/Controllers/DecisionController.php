<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDecisionRequest;
use App\Http\Requests\UpdateDecisionRequest;
use App\Models\Decision;
use Illuminate\Support\Facades\DB;

class DecisionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Decision = Decision::latest()->paginate(10);
        $Decision = Decision::latest()->get();

        if ($Decision->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $Decision,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found',
            ];

            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreDecisionRequest $request)
    {
        $Decision = Decision::create($request->all());

        if ($Decision != null) {
            $response = [
                'value' => true,
                'data' => $Decision,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Decision = Decision::where('patient_id', $id)->first();

        if ($Decision != null) {
            $response = [
                'value' => true,
                'data' => $Decision,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDecisionRequest $request, $id)
    {
        $Decision = Decision::where('patient_id', $id)->first();

        if ($Decision != null) {
            $Decision->update($request->all());

            DB::table('sections')->where('patient_id', $id)->update(['section_7' => true]);
            /*
            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();

            $incrementAmount = 5; // Example increment amount
            $action = 'Update Medical decision Section'; // Example action

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
                'data' => $Decision,
                'message' => 'Decision Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Decision = Decision::where('patient_id', $id)->first();

        if ($Decision != null) {
            DB::table('Decisions')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Decision Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found',
            ];

            return response($response, 404);
        }
    }
}
