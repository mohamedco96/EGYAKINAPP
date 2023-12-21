<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRiskRequest;
use App\Http\Requests\UpdateRiskRequest;
use App\Models\Risk;
use Illuminate\Support\Facades\DB;

class RiskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Risk = Risk::latest()->paginate(10);
        $Risk = Risk::latest()->get();

        if ($Risk != null) {
            $response = [
                'value' => true,
                'data' => $Risk,
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
    public function store(StoreRiskRequest $request)
    {
        $Risk = Risk::create($request->all());

        if ($Risk != null) {
            $response = [
                'value' => true,
                'data' => $Risk,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Risk was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Risk = Risk::where('patient_id', $id)->first();

        if ($Risk != null) {
            $response = [
                'value' => true,
                'data' => $Risk,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Risk was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRiskRequest $request, $id)
    {
        $Risk = Risk::where('patient_id', $id)->first();

        if ($Risk != null) {
            $Risk->update($request->all());

            DB::table('sections')->where('patient_id', $id)->update(['section_4' => true]);
            /*
            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();

            $incrementAmount = 5; // Example increment amount
            $action = 'Update Risk factors for AKI Section'; // Example action

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
                'data' => $Risk,
                'message' => 'Risk Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Risk was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Risk = Risk::where('patient_id', $id)->first();

        if ($Risk != null) {
            DB::table('risks')->where('patient_id', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'Risk Deleted Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Risk was found',
            ];

            return response($response, 404);
        }
    }
}
