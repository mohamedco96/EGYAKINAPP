<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use Illuminate\Support\Facades\Auth;
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

        if($complaint!=null){
            $response = [
                'value' => true,
                'data' => $complaint
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
    public function store(StoreComplaintRequest $request)
    {
        $complaint = Complaint::create($request->all());

        if($complaint!=null){
            $response = [
                'value' => true,
                'data' => $complaint
            ];
            return response($response, 200);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $complaint = Complaint::find($id);

        if($complaint!=null){
            $response = [
                'value' => true,
                'data' => $complaint
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateComplaintRequest $request, $id)
    {
        $complaint = Complaint::find($id);

        if($complaint!=null){
            $complaint->update($request->all());
            $response = [
                'value' => true,
                'data' => $complaint,
                'message' => 'Complaint Updated Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $complaint = Complaint::find($id);

        if($complaint!=null){
            Complaint::destroy($id);
            $response = [
                'value' => true,
                'message' => 'Complaint Deleted Successfully'
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Complaint was found'
            ];
            return response($response, 404);
        }
    }
}
