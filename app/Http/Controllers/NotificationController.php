<?php

namespace App\Http\Controllers;

//use App\Models\Notification; //will rename it
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;


class NotificationController extends Controller
{
    protected $messaging;

    public function __construct(FirebaseMessaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function send(Request $request)
    {
        $deviceToken = $request->input('token');
        $title = $request->input('title');
        $body = $request->input('body');

        $notification = Notification::create($title, $body);
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        $this->messaging->send($message);

        return response()->json(['status' => 'Message sent successfully']);
    }

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

            // Fetch today's records
            $todayRecords = Notification::where('doctor_id', $doctorId)
                ->whereDate('created_at', $today)
                ->with([
                    'patient' => function ($query) {
                        $query->select('id', 'doctor_id', 'updated_at');
                    },
                    'patient.doctor' => function ($query) {
                        $query->select('id', 'name', 'lname', 'workingplace', 'image');
                    },
                    'patient.answers' => function ($query) {
                        $query->select('id', 'patient_id', 'answer', 'question_id');
                    },
                    'patient.status' => function ($query) {
                        $query->select('id', 'patient_id', 'key', 'status');
                    }
                ])
                ->latest()
                ->get();

            // Transform today's records
            $transformedTodayRecords = $todayRecords->map(function ($notification) {
                $name = optional($notification->patient->answers->where('question_id', 1)->first())->answer;
                $hospital = optional($notification->patient->answers->where('question_id', 2)->first())->answer;
                $governorate = optional($notification->patient->answers->where('question_id', 11)->first())->answer;

                $submitStatus = optional($notification->patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($notification->patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                return [
                    'id' => $notification->id,
                    'read' => $notification->read,
                    'type' => $notification->type,
                    'patient_id' => $notification->patient_id,
                    'doctor_id' => $notification->doctor_id,
                    'created_at' => $notification->created_at,
                    'patient' => [
                        'id' => $notification->patient_id,
                        'name' => $name,
                        'hospital' => $hospital,
                        'governorate' => $governorate,
                        'doctor_id' => $notification->patient->doctor->id,
                        'doctor' => $notification->patient->doctor,
                        'sections' => [
                            'submit_status' => $submitStatus ?? false,
                            'outcome_status' => $outcomeStatus ?? false,
                        ]
                    ],
                ];
            });

            // Fetch recent records
            $recentRecords = Notification::where('doctor_id', $doctorId)
                ->whereDate('created_at', '<', $today)
                ->with([
                    'patient' => function ($query) {
                        $query->select('id', 'doctor_id', 'updated_at');
                    },
                    'patient.doctor' => function ($query) {
                        $query->select('id', 'name', 'lname', 'workingplace', 'image');
                    },
                    'patient.answers' => function ($query) {
                        $query->select('id', 'patient_id', 'answer', 'question_id');
                    },
                    'patient.status' => function ($query) {
                        $query->select('id', 'patient_id', 'key', 'status');
                    }
                ])
                ->latest()
                ->get();

            // Transform recent records
            $transformedRecentRecords = $recentRecords->map(function ($notification) {
                $name = optional($notification->patient->answers->where('question_id', 1)->first())->answer;
                $hospital = optional($notification->patient->answers->where('question_id', 2)->first())->answer;
                $governorate = optional($notification->patient->answers->where('question_id', 11)->first())->answer;

                $submitStatus = optional($notification->patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($notification->patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                return [
                    'id' => $notification->id,
                    'read' => $notification->read,
                    'type' => $notification->type,
                    'patient_id' => $notification->patient_id,
                    'doctor_id' => $notification->doctor_id,
                    'created_at' => $notification->created_at,
                    'patient' => [
                        'id' => $notification->patient_id,
                        'name' => $name,
                        'hospital' => $hospital,
                        'governorate' => $governorate,
                        'doctor_id' => $notification->patient->doctor->id,
                        'doctor' => $notification->patient->doctor,
                        'sections' => [
                            'submit_status' => $submitStatus ?? false,
                            'outcome_status' => $outcomeStatus ?? false,
                        ]
                    ],
                ];
            });

            // Paginate the transformed data for recent records
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 10;
            $slicedData = $transformedRecentRecords->slice(($currentPage - 1) * $perPage, $perPage);
            $transformedPatientsPaginated = new LengthAwarePaginator($slicedData->values(), count($transformedRecentRecords), $perPage);



            // Count unread notifications
            $unreadCount = Notification::where('doctor_id', $doctorId)->where('read', false)->count();

            // Prepare response
            $response = [
                'value' => true,
                'unreadCount' => strval($unreadCount),
                'todayRecords' => $transformedTodayRecords,
                'recentRecords' => $transformedPatientsPaginated
            ];

            // Log successful response
            Log::info('Successfully fetched new notifications.', ['doctor_id' => $doctorId]);

            Notification::where('doctor_id', $doctorId)->update(['read' => true]);

            return response()->json($response, 200);

            // Update notifications as read
        } catch (\Exception $e) {
            // Log error
            Log::error('Error occurred while fetching new notifications: ' . $e->getMessage());
            return response()->json(['value' => false, 'message' => 'Failed to fetch new notifications'], 500);
        }
    }


    // Other methods remain unchanged
}
