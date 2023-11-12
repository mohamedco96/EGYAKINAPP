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
        //$notifications = Notification::latest()->paginate(10);
        $notifications = Notification::latest()->get();

        if ($notifications->isNotEmpty()) {
            $response = [
                'value' => true,
                'data' => $notifications,
            ];

            return response($response, 201);
        } else {
            $response = [
                'value' => false,
            ];

            return response($response, 404);
        }
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
            ->with('patient:id,name,hospital,governorate,doctor_id')
            ->latest()
            ->get();

        $unreadCount = $notifications->where('read', false)->count();

        if ($notifications->isNotEmpty()) {
            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
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

        $notifications = Notification::where('doctor_id', $doctorId);

        if ($notifications->exists()) {
            $notifications->update(['read' => true]);
            $response = [
                'value' => true,
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
}