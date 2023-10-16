<?php

namespace App\Http\Controllers;

use App\Models\Outcome;
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
        $Outcome = Outcome::create($request->all());

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
    public function show($id)
    {
        $Outcome = Outcome::where('patient_id', $id)->first();

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
