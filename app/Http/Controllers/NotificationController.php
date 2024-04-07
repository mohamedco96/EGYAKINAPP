<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

    public function showNew(Request $request)
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
            ->paginate($request->input('per_page', 10)); // Default per page limit is 10

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
    public function update(Request $request,$id)
    {
        $doctorId = auth()->user()->id;

        $notifications = Notification::where('doctor_id', $doctorId)
        ->where('id', $id);

        if ($notifications->exists()) {
            $notifications->update($request->all());
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

    public function markAllAsRead()
    {
        $doctorId = auth()->user()->id;

        $notifications = Notification::where('doctor_id', $doctorId);

        if ($notifications->exists()) {
            $notifications->update(['read' => true]);
            $response = [
                'value' => true,
                'message' => 'Notifications Updated Successfully',
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $notification = Notification::create($request->all());

        $response = [
            'value' => true,
            'data' => $notification,
            'message' => 'Notification created successfully',
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $doctorId = auth()->user()->id;
        $notification = Notification::where('doctor_id', $doctorId)->find($id);


        if ($notification) {
            $notification->delete();
            $response = [
                'value' => true,
                'message' => 'Notification deleted successfully',
            ];
            return response()->json($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'Notification not found',
            ];
            return response()->json($response, 404);
        }
    }
}
