<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //$notifications = Notification::latest()->paginate(10);
        $notifications = Notification::latest()->get();
        $unreadCount = $notifications->where('read', false)->count();

            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];

            return response($response, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $doctorId = auth()->user()->id;

        $notifications = Notification::where('doctor_id', $doctorId)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with('patient.doctor:id,name,lname,workingplace')
            ->with('patient.sections:id,submit_status,outcome_status,patient_id')
            ->with('patient:id,name,hospital,governorate,doctor_id')
            ->latest()
            ->get();

        $unreadCount = $notifications->where('read', false)->count();

            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];

            return response($response, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        $doctorId = auth()->user()->id;

        $notifications = Notification::where('doctor_id', $doctorId);

        if ($notifications->exists()) {
            $notifications->update(['read' => true]);
            $response = [
                'value' => true,
                'message' => 'Notification Updated Successfully',
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Notification was found',
            ];

            return response($response, 404);
        }
    }
}
