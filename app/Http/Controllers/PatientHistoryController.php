<?php

namespace App\Http\Controllers;

use App\Models\PatientHistory;
use App\Models\Section;
use App\Http\Requests\StorePatientHistoryRequest;
use App\Http\Requests\UpdatePatientHistoryRequest;
use Illuminate\Support\Facades\DB;

class PatientHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Patient = PatientHistory::all();

        if($Patient!=null){
            $response = [
                'value' => true,
                'data' => $Patient
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
    public function store(StorePatientHistoryRequest $request)
    {
        $request->validate([
            'user_id' => 'required|string|exists:App\Models\User,id',
            'name' => 'required|string',
            'hospital' => 'required|string',
            'collected_data_from' => 'required|string',
            'NID' => 'string|size:14',
            'phone' => 'string|size:11',
            'email' => 'email|string',
            'age' => 'required|string',
            'gender' => 'required|string',
            'occupation' => 'required|string',
            'residency' => 'required|string',
            'governorate' => 'required|string',
            'marital_status' => 'required|string',
            'educational_level' => 'required|string',
            'special_habits_of_the_patient' => 'required|string',
            'DM' => 'required|string',
            'DM_duration' => 'interger',
            'HTN' => 'required|string',
            'HTN_duration' => 'interger'
        ]);

        $Patient = PatientHistory::create($request->all());
        

        if($Patient!=null){
            $section = Section::create([
                'user_id' => $request['user_id'],
                'patient_id' => $Patient['id'],
                'section_1' => true,
            ]);
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return $Patient;
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
            $response = [
                'value' => true,
                'data' => $Patient
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePatientHistoryRequest $request, $id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
            $Patient->update($request->all());
            $response = [
                'value' => true,
                'data' => $Patient,
                'message' => 'Patient Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $Patient = PatientHistory::find($id);

        if($Patient!=null){
            PatientHistory::destroy($id);
            DB::table('sections')->where('patient_id', '=', $id)->delete();
           // Product::where('name','like','%'.$name.'%')->get();
            $response = [
                'value' => true,
                'message' => 'Patient Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Patient was found'
            ];
            return response($response, 404);
        }
    }
}