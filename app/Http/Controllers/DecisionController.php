<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Http\Requests\StoreDecisionRequest;
use App\Http\Requests\UpdateDecisionRequest;
use Illuminate\Support\Facades\Auth;
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

        if($Decision->isNotEmpty()){
            $response = [
                'value' => true,
                'data' => $Decision
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found'
            ];
            return response($response, 404);
        }
    }

    //@param \Illuminate\Http\Request $request
    // @return \Illuminate\Http\Response
    public function store(StoreDecisionRequest $request)
    {
        $Decision = Decision::create($request->all());

        if($Decision!=null){
            $response = [
                'value' => true,
                'data' => $Decision
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found'
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

        if($Decision!=null){
            $response = [
                'value' => true,
                'data' => $Decision
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found'
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

        if($Decision!=null){
            $Decision->update($request->all());
            $response = [
                'value' => true,
                'data' => $Decision,
                'message' => 'Decision Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found'
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

        if($Decision!=null){
            DB::table('Decisions')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'Decision Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Decision was found'
            ];
            return response($response, 404);
        }
    }
}
