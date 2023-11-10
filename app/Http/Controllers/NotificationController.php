<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Notification = Notification::latest()->paginate(10);
        $Notification = Notification::latest()->get();

        if($Notification!=null){
            $response = [
                'value' => true,
                'data' => $Notification
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
    public function store(StoreNotificationRequest $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $Notification = Notification::where('doctor_id', $id)
        ->select('id','read','type','patient_id','doctor_id','updated_at')
        ->with('doctor:id,name,lname,workingplace')
        ->with('patient:id,name,hospital,governorate')
        ->latest()
        ->get();

        $unreadCount = Notification::where('doctor_id', $id)->where('read', false)->count();

        if($Notification!=null){
            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $Notification
            ];
            return response($response, 201);
        }else {
            $response = [
                'value' => false,
                'message' => 'No Notification was found'
            ];
            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificationRequest $request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

    }
}
