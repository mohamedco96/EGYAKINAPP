<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Http\Requests\StoreSectionRequest;
use App\Http\Requests\UpdateSectionRequest;
use Illuminate\Support\Facades\DB;

class SectionController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $section = Section::all();

        if($section!=null){
            $response = [
                'value' => true,
                'data' => $section
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

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $section = Section::where('patient_id', $id)->first();

        if($section!=null){
            $response = [
                'value' => true,
                'data' => $section
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

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $section = Section::where('patient_id', $id)->first();

        if($section!=null){
            DB::table('sections')->where('patient_id', $id)->delete();
            $response = [
                'value' => true,
                'message' => 'section Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No section was found'
            ];
            return response($response, 404);
        }
    }
}
