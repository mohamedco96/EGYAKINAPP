<?php

namespace App\Http\Controllers;

use App\Models\Cause;
use App\Http\Requests\StoreCauseRequest;
use App\Http\Requests\UpdateCauseRequest;
use Illuminate\Support\Facades\DB;

class CauseController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Cause = Cause::latest()->paginate(10);
        $Cause = Cause::latest()->get();

        if($Cause!=null){
            $response = [
                'value' => true,
                'data' => $Cause
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreCauseRequest $request)
    {
        $Cause = Cause::create($request->all());

        if($Cause!=null){
            $response = [
                'value' => true,
                'data' => $Cause
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Cause was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Cause = Cause::where('patient_id', $id)->first();

        if($Cause!=null){
            $response = [
                'value' => true,
                'data' => $Cause
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Cause was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCauseRequest $request, $id)
    {
        $Cause = Cause::where('patient_id', $id)->first();

        if($Cause!=null){
            $Cause->update($request->all());
            
            DB::table('sections')->where('patient_id', $id)->update(['section_3' => true]);

            //scoring system
            $doctorId = auth()->user()->id; // Assuming you have authentication in place
            $score = Score::where('doctor_id', $doctorId)->first();
            
            $incrementAmount = 5; // Example increment amount
            $action = 'Update Cause of AKI Section'; // Example action
            
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
                'data' => $Cause,
                'message' => 'Cause Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Cause was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Cause = Cause::where('patient_id', $id)->first();

        if($Cause!=null){
            DB::table('causes')->where('patient_id', $id)->delete();

            $response = [
                'value' => true,
                'message' => 'Cause Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Cause was found'
            ];
            return response($response, 404);
        }
    }
}
