<?php

namespace App\Http\Controllers;

use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Traits\FormatsUserName;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationController extends Controller
{
    use FormatsUserName;

    protected $messaging;

    public function __construct(FirebaseMessaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendold(Request $request)
    {
        $deviceToken = $request->input('token');
        $title = $request->input('title');
        $body = $request->input('body');

        $notification = Notification::create($title, $body);
        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        $this->messaging->send($message);

        return response()->json(['status' => __('api.message_sent_successfully')]);
    }

    /**
     * Send a message to all FCM tokens.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        try {
            // Retrieve all tokens from the fcm_tokens table
            $tokens = FcmToken::pluck('token')->toArray();

            if (empty($tokens)) {
                Log::info(__('api.no_fcm_tokens_found'));

                return response()->json(['status' => __('api.no_tokens_found')], 404);
            }

            $title = $request->input('title');
            $body = $request->input('body');

            $notification = Notification::create($title, $body);

            $messages = [];
            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);
            }

            // Send messages in bulk
            $this->messaging->sendAll($messages);

            Log::info('Message sent successfully to all tokens.', [
                'title' => $title,
                'body' => $body,
                'tokens_count' => count($tokens),
            ]);

            return response()->json(['status' => __('api.message_sent_to_all_tokens')], 200);
        } catch (\Exception $e) {
            Log::error('Exception occurred while sending message.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return response()->json(['status' => __('api.failed_to_send_message')], 500);
        }
    }

    public function sendPushNotification($title, $body, $tokens)
    {
        try {
            // Retrieve all tokens from the fcm_tokens table
            //            $tokens = FcmToken::pluck('token')->toArray();

            if (empty($tokens)) {
                Log::info(__('api.no_fcm_tokens_found'));

                return response()->json(['status' => __('api.no_tokens_found')], 404);
            }

            //            $title = $request->input('title');
            //            $body = $request->input('body');

            $notification = Notification::create($title, $body);

            $messages = [];
            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);
            }

            // Send messages in bulk
            $this->messaging->sendAll($messages);

            Log::info('Message sent successfully to all tokens.', [
                'title' => $title,
                'body' => $body,
                'tokens_count' => count($tokens),
            ]);

            return response()->json(['status' => __('api.message_sent_to_all_tokens')], 200);
        } catch (\Exception $e) {
            Log::error('Exception occurred while sending message.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return response()->json(['status' => __('api.failed_to_send_message')], 500);
        }
    }

    public function sendAllPushNotification(Request $request)
    {
        try {
            //            // Use input() or get() to retrieve request data
            //            $title = $request->input('title');
            //            $body = $request->input('body');

            $title = 'EgyAkin v1.0.9 is Here! ✨';
            $body = 'Kidney community is here! Post, explore #DialysisSupport, join groups, and enjoy a smoother experience.🔄 Update now for the latest features! 🚀';
            // Retrieve all tokens from the fcm_tokens table
            $tokens = FcmToken::pluck('token')->toArray();

            if (empty($tokens)) {
                Log::info(__('api.no_fcm_tokens_found'));

                return response()->json(['status' => __('api.no_tokens_found')], 404);
            }

            $notification = Notification::create($title, $body);

            $messages = [];
            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);
            }

            // Send messages in bulk
            $this->messaging->sendAll($messages);

            Log::info('Message sent successfully to all tokens.', [
                'title' => $title,
                'body' => $body,
                'tokens_count' => count($tokens),
            ]);

            return response()->json(['status' => __('api.message_sent_to_all_tokens')], 200);
        } catch (\Exception $e) {
            Log::error('Exception occurred while sending message.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            return response()->json(['status' => __('api.failed_to_send_message')], 500);
        }
    }

    /**
     * Store a newly created FCM token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFCM(Request $request)
    {
        $doctorId = Auth::id();

        // Validate the request
        $request->validate([
            //'doctor_id' => 'required|exists:users,id',
            //'token' => 'required|unique:fcm_tokens,token',
        ]);

        try {
            // Attempt to create a new FCM token
            $fcmToken = FcmToken::create([
                'doctor_id' => $doctorId,
                'token' => $request->token,
            ]);

            // Log the successful token storage
            Log::info('FCM token stored successfully.', [
                'doctor_id' => $doctorId,
                'token' => $request->token,
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'message' => __('api.fcm_token_stored_successfully'),
            ], 201);
        } catch (QueryException $e) {
            // Check for duplicate token error
            if ($e->errorInfo[1] == 1062) {
                // Log the duplicate token error
                Log::error('Duplicate FCM token error.', [
                    'doctor_id' => $doctorId,
                    'token' => $request->token,
                ]);

                // Return error response for duplicate token
                return response()->json([
                    'value' => false,
                    'message' => __('api.fcm_token_already_exists'),
                ], 409);
            }

            // Log any other database errors
            Log::error('Database error while storing FCM token.', [
                'message' => $e->getMessage(),
                'doctor_id' => $doctorId,
                'token' => $request->token,
            ]);

            // Return general error response
            return response()->json([
                'value' => false,
                'message' => __('api.failed_to_store_fcm_token'),
            ], 500);
        } catch (\Exception $e) {
            // Log any other exceptions that occur
            Log::error('Exception occurred while storing FCM token.', [
                'message' => $e->getMessage(),
                'doctor_id' => $doctorId,
                'token' => $request->token,
            ]);

            // Return general error response
            return response()->json([
                'value' => false,
                'message' => __('api.failed_to_store_fcm_token'),
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $notifications = AppNotification::latest()->get();
            $unreadCount = $notifications->where('read', false)->count();

            $response = [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];

            return response($response, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching notifications: '.$e->getMessage());

            return response()->json(['value' => false, 'message' => __('api.failed_to_fetch_notifications')], 500);
        }
    }

    public function showNew(Request $request)
    {
        try {
            $doctorId = auth()->user()->id;
            $today = Carbon::today();

            // Fetch today's and recent records in one go
            $notifications = AppNotification::where('doctor_id', $doctorId)
                ->select('id', 'read', 'content', 'type', 'type_id', 'patient_id', 'doctor_id', 'type_doctor_id', 'localization_key', 'localization_params', 'created_at')
                ->with([
                    'patient' => function ($query) {
                        $query->select('id', 'doctor_id', 'updated_at');
                    },
                    'patient.doctor' => function ($query) {
                        $query->select('id', 'name', 'lname', 'workingplace', 'image', 'isSyndicateCardRequired');
                    },
                    'patient.answers' => function ($query) {
                        $query->select('id', 'patient_id', 'answer', 'question_id')
                            ->whereIn('question_id', [1, 2, 11]);
                    },
                    'patient.status' => function ($query) {
                        $query->select('id', 'patient_id', 'key', 'status')
                            ->whereIn('key', ['submit_status', 'outcome_status']);
                    },
                    'typeDoctor:id,name,lname,workingplace,image,isSyndicateCardRequired', // Eager load typeDoctor
                ])
                ->latest()
                ->get()
                ->groupBy(function ($notification) {
                    return Carbon::parse($notification->created_at)->isToday() ? 'today' : 'recent';
                });

            // Transform data
            $transformedTodayRecords = $this->fetchAndTransformNotifications($notifications['today'] ?? collect());
            $transformedRecentRecords = $this->fetchAndTransformNotifications($notifications['recent'] ?? collect());

            // Paginate recent records
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 10;
            $slicedData = $transformedRecentRecords->slice(($currentPage - 1) * $perPage, $perPage);
            $transformedPatientsPaginated = new LengthAwarePaginator($slicedData->values(), count($transformedRecentRecords), $perPage);

            // Count unread notifications
            $unreadCount = AppNotification::where('doctor_id', $doctorId)->where('read', false)->count();

            // Prepare response
            $response = [
                'value' => true,
                'unreadCount' => strval($unreadCount),
                'todayRecords' => $transformedTodayRecords,
                'recentRecords' => $transformedPatientsPaginated,
            ];

            // Log successful response
            Log::info('Successfully fetched new notifications.', ['doctor_id' => $doctorId]);

            // Mark only unread notifications as read in bulk
            AppNotification::where('doctor_id', $doctorId)
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Log error
            Log::error('Error occurred while fetching new notifications: '.$e->getMessage());

            return response()->json(['value' => false, 'message' => __('api.failed_to_fetch_new_notifications')], 500);
        }
    }

    private function fetchAndTransformNotifications($notifications)
    {
        return $notifications->map(function ($notification) {
            if ($notification->patient) {
                $name = optional($notification->patient->answers->where('question_id', 1)->first())->answer;
                $hospital = optional($notification->patient->answers->where('question_id', 2)->first())->answer;
                $governorate = optional($notification->patient->answers->where('question_id', 11)->first())->answer;

                $submitStatus = optional($notification->patient->status->where('key', 'LIKE', 'submit_status')->first())->status;
                $outcomeStatus = optional($notification->patient->status->where('key', 'LIKE', 'outcome_status')->first())->status;

                $doctor = $notification->patient->doctor;
                $doctorDetails = [
                    'id' => optional($doctor)->id,
                    'name' => optional($doctor)->name,
                    'lname' => optional($doctor)->lname,
                    'workingplace' => optional($doctor)->workingplace,
                    'image' => optional($doctor)->image,
                ];
            } else {
                $name = $hospital = $governorate = null;
                $submitStatus = $outcomeStatus = false;
                $doctorDetails = null;
            }

            $patientDetails = $notification->patient ? [
                'id' => strval($notification->patient_id),
                'name' => $name,
                'hospital' => $hospital,
                'governorate' => $governorate,
                'doctor_id' => optional($notification->patient->doctor)->id,
                'doctor' => $doctorDetails,
                'sections' => [
                    'submit_status' => $submitStatus ?? false,
                    'outcome_status' => $outcomeStatus ?? false,
                ],
            ] : (object) [
                'id' => null,
                'name' => null,
                'hospital' => null,
                'governorate' => null,
                'doctor_id' => null,
                'doctor' => (object) [
                    'id' => null,
                    'name' => null,
                    'lname' => null,
                    'workingplace' => null,
                    'image' => null,
                    'isSyndicateCardRequired' => null,
                ],
                'sections' => [
                    'submit_status' => false,
                    'outcome_status' => false,
                ],
            ];

            // Use eager loaded typeDoctor data directly
            $typeDoctor = $notification->typeDoctor ?? (object) [
                'id' => null,
                'name' => null,
                'lname' => null,
                'workingplace' => null,
                'image' => null,
                'isSyndicateCardRequired' => null,
            ];

            // Get dynamic localized content with proper user name formatting
            $localizedContent = $this->getLocalizedNotificationContent($notification, $typeDoctor);

            return [
                'id' => $notification->id,
                'read' => $notification->read,
                'content' => $localizedContent,
                'type' => $notification->type,
                'type_id' => $notification->type_id,
                'patient_id' => strval($notification->patient_id),
                'doctor_id' => strval($notification->doctor_id),
                'created_at' => $notification->created_at,
                'patient' => $patientDetails,
                'type_doctor' => $typeDoctor,
            ];
        });
    }

    /**
     * Get localized notification content with proper user name formatting
     */
    private function getLocalizedNotificationContent($notification, $typeDoctor): string
    {
        // If we have localization data, use dynamic translation
        if ($notification->localization_key && $notification->localization_params) {
            $params = $notification->localization_params;

            // Format user names with Dr. prefix if they exist in params
            if (isset($params['name']) && $typeDoctor) {
                $params['name'] = $this->formatUserName($typeDoctor);
            }

            // Handle other name parameters that might exist
            if (isset($params['owner_name']) && $typeDoctor) {
                $params['owner_name'] = $this->formatUserName($typeDoctor);
            }

            if (isset($params['remover_name']) && $typeDoctor) {
                $params['remover_name'] = $this->formatUserName($typeDoctor);
            }

            // Get localized content using current app locale
            return __($notification->localization_key, $params);
        }

        // Fallback to static content if no localization data
        return $notification->content ?? '';
    }
}
