<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $notifications = Notification::latest()->get();
            $unreadCount = $notifications->where('read', false)->count();

            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];

            return response($response, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching notifications: ' . $e->getMessage());
            return response()->json(['value' => false, 'message' => 'Failed to fetch notifications'], 500);
        }
    }

    public function showNew(Request $request)
    {
        try {
            $doctorId = auth()->user()->id;
            $today = Carbon::today();

            $todayRecords = $this->getTodayRecords($doctorId, $today);
            $recentRecords = $this->getRecentRecords($doctorId, $today, $request->input('per_page', 10));

            $unreadCount = Notification::where('doctor_id', $doctorId)->where('read', false)->count();

            Notification::where('doctor_id', $doctorId)->update(['read' => true]);

            $response = [
                'value' => true,
                'unreadCount' => strval($unreadCount),
                'todayRecords' => $todayRecords,
                'recentRecords' => $recentRecords
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching new notifications: ' . $e->getMessage());
            return response()->json(['value' => false, 'message' => 'Failed to fetch new notifications'], 500);
        }
    }

    private function getTodayRecords($doctorId, $today)
    {
        return Notification::where('doctor_id', $doctorId)
            ->whereDate('created_at', $today)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with(['patient' => function ($query) {
                $query->select('id', 'doctor_id', 'updated_at');
            }])
            ->with(['patient.doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'workingplace', 'image');
            }])
            ->with(['patient.answers' => function ($query) {
                $query->select('id', 'patient_id', 'answer')
                    ->whereIn('question_id', [1, 2, 11]); // Adjusted condition using whereIn
            }])
            ->with(['patient.status' => function ($query) {
                $query->select('id', 'patient_id', 'key', 'status')
                    ->where(function ($query) {
                        $query->where('key', 'LIKE', 'submit_status')
                            ->orWhere('key', 'LIKE', 'outcome_status');
                    });
            }])
            ->latest()
            ->get();
    }

    private function getRecentRecords($doctorId, $today, $perPage)
    {
        return Notification::where('doctor_id', $doctorId)
            ->whereDate('created_at', '<', $today)
            ->select('id', 'read', 'type', 'patient_id', 'doctor_id', 'created_at')
            ->with(['patient' => function ($query) {
                $query->select('id', 'doctor_id', 'updated_at');
            }])
            ->with(['patient.doctor' => function ($query) {
                $query->select('id', 'name', 'lname', 'workingplace', 'image');
            }])
            ->with(['patient.answers' => function ($query) {
                $query->select('id', 'patient_id', 'answer')
                    ->whereIn('question_id', [1, 2, 11]); // Adjusted condition using whereIn
            }])
            ->with(['patient.status' => function ($query) {
                $query->select('id', 'patient_id', 'key', 'status')
                    ->where(function ($query) {
                        $query->where('key', 'LIKE', 'submit_status')
                            ->orWhere('key', 'LIKE', 'outcome_status');
                    });
            }])
            ->latest()
            ->paginate($perPage);
    }

    // Other methods remain unchanged
}
