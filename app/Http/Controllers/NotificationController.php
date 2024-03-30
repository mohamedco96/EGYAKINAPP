<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;

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

    public function showNew()
    {
        // Get today's date
        $today = Carbon::today();

        $doctorId = auth()->user()->id;

        // Get records created today
        $todayRecords = Notification::where('doctor_id', $doctorId)
            ->whereDate('created_at', $today)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with('patient.doctor:id,name,lname,workingplace')
            ->with('patient.sections:id,submit_status,outcome_status,patient_id')
            ->with('patient:id,name,hospital,governorate,doctor_id')
            ->latest()
            ->get();

        // Get records created recently (excluding today)
        $recentRecords = Notification::where('doctor_id', $doctorId)
            ->where('created_at', '>', $today)
            ->orWhereDate('created_at', '<', $today)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with('patient.doctor:id,name,lname,workingplace')
            ->with('patient.sections:id,submit_status,outcome_status,patient_id')
            ->with('patient:id,name,hospital,governorate,doctor_id')
            ->latest()
            ->get();

        $unreadCount = Notification::where('doctor_id', $doctorId)
                        ->where('read', false)->count();

        $response = [
            'value' => true,
            'unreadCount' => strval($unreadCount),
            'todayRecords' => $todayRecords,
            'recentRecords' => $recentRecords
        ];

        return response()->json($response, 200);

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
