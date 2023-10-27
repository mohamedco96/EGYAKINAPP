<?php

namespace App\Http\Controllers;

use App\Models\Outcome;
use App\Models\Score;
use App\Models\ScoreHistory;
use App\Http\Requests\StoreOutcomeRequest;
use App\Http\Requests\UpdateOutcomeRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutcomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Outcome = Outcome::latest()->paginate(10);
        $Outcome = Outcome::latest()->get();

        if($Outcome->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Outcome
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found'
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreOutcomeRequest $request)
    {
        $Outcome = Outcome::create([
            'doctor_id' => Auth::id(),
            'patient_id' => $request->patient_id,
            'outcome_of_the_patient' => $request->outcome_of_the_patient,
            'creatinine_on_discharge' => $request->creatinine_on_discharge,
            'final_status' => $request->final_status,
            'other' => $request->other
        ]);

        DB::table('sections')->where('patient_id', $request->patient_id)->update(['outcome_status' => true]);

        //scoring system
        $doctorId = Auth::id(); // Assuming you have authentication in place
        $score = Score::where('doctor_id', $doctorId)->first();
        
        $incrementAmount = 5; // Example increment amount
        $action = 'Add Outcome'; // Example action
        
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

        if($Outcome!=null){
            $response = [
                'value' => true,
                'data' => $Outcome
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($patient_id)
    {
        $Outcome = Outcome::where('patient_id', $patient_id)
        ->select('doctor_id','outcome_of_the_patient', 'creatinine_on_discharge', 'final_status', 'other', 'updated_at')
        ->with('doctor:id,name,lname')            
        ->first();

        $data = [
            'first_name' => $Outcome->name,
            'last_name' => $Outcome->lname,
        ];

        if($Outcome!=null){
            $response = [
                'value' => true,
                'data' => $Outcome
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOutcomeRequest $request, $id)
    {
        $Outcome = Outcome::where('patient_id', $id)->first();

        if($Outcome!=null){
            $Outcome->update($request->all());
            $response = [
                'value' => true,
                'data' => $Outcome,
                'message' => 'Outcome Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Outcome = Outcome::where('patient_id', $id)->first();

        if($Outcome!=null){
            DB::table('Outcomes')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Outcome Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Outcome was found'
            ];
            return response($response, 404);
        }
    }
}
