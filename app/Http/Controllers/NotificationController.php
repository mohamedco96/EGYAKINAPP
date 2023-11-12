<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNotificationRequest;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$Notification = Notification::latest()->paginate(10);
        $Notification = Notification::latest()->get();

        if ($Notification != null) {
            $response = [
                'value' => true,
                'data' => $Notification,
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
    public function store(StoreNotificationRequest $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $doctorId = auth()->user()->id;

        $Notification = Notification::where('doctor_id', $doctorId)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with('patient.doctor:id,name,lname,workingplace')
            ->with('patient:id,name,hospital,governorate,doctor_id')
            ->latest()
            ->get();

        $unreadCount = Notification::where('doctor_id', $doctorId)->where('read', false)->count();

        if ($Notification != null) {
            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $Notification,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Notification was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        $doctorId = auth()->user()->id;

        $Notification = Notification::where('doctor_id', $doctorId)->get();

        if ($Notification != null) {
            $Notification = Notification::where('doctor_id', $doctorId)->update(['read' => true]);
            $response = [
                'value' => true,
                'data' => $Notification,
                'message' => 'Notification Updated Successfully',
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Notification was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

    }
}
