<?php

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging as FirebaseMessaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    protected $messaging;

    public function __construct(FirebaseMessaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send notification to a single device token
     */
    public function sendSingleNotification(string $deviceToken, string $title, string $body): array
    {
        try {
            $notification = Notification::create($title, $body);
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification);

            $this->messaging->send($message);

            Log::info('Single notification sent successfully', [
                'title' => $title,
                'token' => substr($deviceToken, 0, 10).'...',
            ]);

            return ['success' => true, 'message' => 'Message sent successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to send single notification', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send notification to all FCM tokens
     */
    public function sendToAllTokens(string $title, string $body): array
    {
        try {
            $tokens = FcmToken::pluck('token')->toArray();

            if (empty($tokens)) {
                Log::info('No FCM tokens found for broadcast');

                return ['success' => false, 'status' => 'No tokens found'];
            }

            $notification = Notification::create($title, $body);
            $messages = [];

            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);
            }

            $this->messaging->sendAll($messages);

            Log::info('Broadcast notification sent successfully', [
                'title' => $title,
                'body' => $body,
                'tokens_count' => count($tokens),
            ]);

            return [
                'success' => true,
                'status' => 'Message sent successfully to all tokens',
                'tokens_count' => count($tokens),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send broadcast notification', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send push notification to specific tokens (used by other services)
     */
    public function sendPushNotification(string $title, string $body, array $tokens): array
    {
        try {
            if (empty($tokens)) {
                Log::info('No FCM tokens provided for push notification');

                return ['success' => false, 'status' => 'No tokens found'];
            }

            $notification = Notification::create($title, $body);
            $messages = [];

            foreach ($tokens as $token) {
                $messages[] = CloudMessage::withTarget('token', $token)
                    ->withNotification($notification);
            }

            $this->messaging->sendAll($messages);

            Log::info('Push notification sent successfully', [
                'title' => $title,
                'body' => $body,
                'tokens_count' => count($tokens),
            ]);

            return [
                'success' => true,
                'status' => 'Message sent successfully to all tokens',
                'tokens_count' => count($tokens),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'title' => $title,
                'tokens_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send pre-defined notification to all users
     */
    public function sendAllPushNotification(): array
    {
        try {
            $title = 'EgyAkin v1.0.9 is Here! ✨';
            $body = 'Kidney community is here! Post, explore #DialysisSupport, join groups, and enjoy a smoother experience.🔄 Update now for the latest features! 🚀';

            return $this->sendToAllTokens($title, $body);
        } catch (\Exception $e) {
            Log::error('Failed to send pre-defined notification to all users', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get all notifications with unread count
     */
    public function getAllNotifications(): array
    {
        try {
            $notifications = AppNotification::latest()->get();
            $unreadCount = $notifications->where('read', false)->count();

            Log::info('Successfully fetched all notifications', [
                'total_count' => $notifications->count(),
                'unread_count' => $unreadCount,
            ]);

            return [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch all notifications', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create a new notification
     */
    public function createNotification(array $data): array
    {
        try {
            $notification = AppNotification::create($data);

            Log::info('Notification created successfully', [
                'notification_id' => $notification->id,
                'type' => $notification->type,
            ]);

            return [
                'value' => true,
                'data' => $notification,
                'message' => 'Notification created successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get a specific notification
     */
    public function getNotification($id): array
    {
        try {
            $notification = AppNotification::find($id);

            if (! $notification) {
                return [
                    'value' => false,
                    'message' => 'Notification not found',
                ];
            }

            Log::info('Notification fetched successfully', [
                'notification_id' => $id,
            ]);

            return [
                'value' => true,
                'data' => $notification,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get notifications for authenticated user
     */
    public function getUserNotifications(): array
    {
        try {
            $doctorId = auth()->id();
            $notifications = AppNotification::where('doctor_id', $doctorId)
                ->latest()
                ->get();

            $unreadCount = $notifications->where('read', false)->count();

            Log::info('User notifications fetched successfully', [
                'doctor_id' => $doctorId,
                'total_count' => $notifications->count(),
                'unread_count' => $unreadCount,
            ]);

            return [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $notifications,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch user notifications', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get new notifications with complex formatting and relationships
     */
    public function getNewNotifications(): array
    {
        try {
            $doctorId = auth()->id();
            $today = Carbon::today();

            // Common eager-load with tight filters to drastically reduce payload size
            $withRelations = [
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
                'typeDoctor:id,name,lname,workingplace,image,isSyndicateCardRequired',
            ];

            // Fetch today's notifications (no pagination)
            $todayNotifications = AppNotification::where('doctor_id', $doctorId)
                ->whereDate('created_at', $today)
                ->select('id', 'read', 'content', 'type', 'type_id', 'patient_id', 'doctor_id', 'type_doctor_id', 'created_at')
                ->with($withRelations)
                ->latest()
                ->get();

            $transformedTodayRecords = $this->transformNotifications($todayNotifications);

            // Fetch recent (before today) notifications with DB-side pagination
            $perPage = 10;
            $recentPaginated = AppNotification::where('doctor_id', $doctorId)
                ->whereDate('created_at', '<', $today)
                ->select('id', 'read', 'content', 'type', 'type_id', 'patient_id', 'doctor_id', 'type_doctor_id', 'created_at')
                ->with($withRelations)
                ->latest()
                ->paginate($perPage);

            // Transform the paginated collection while keeping paginator metadata
            $transformedRecentCollection = $this->transformNotifications($recentPaginated->getCollection());
            $transformedPatientsPaginated = $recentPaginated->setCollection($transformedRecentCollection->values());

            // Count unread notifications
            $unreadCount = AppNotification::where('doctor_id', $doctorId)->where('read', false)->count();

            // Mark unread notifications as read only
            AppNotification::where('doctor_id', $doctorId)
                ->where('read', false)
                ->update(['read' => true]);

            Log::info('New notifications fetched successfully', [
                'doctor_id' => $doctorId,
                'today_count' => $transformedTodayRecords->count(),
                'recent_count' => $recentPaginated->total(),
                'unread_count' => $unreadCount,
            ]);

            return [
                'value' => true,
                'unreadCount' => strval($unreadCount),
                'todayRecords' => $transformedTodayRecords,
                'recentRecords' => $transformedPatientsPaginated,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch new notifications', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a notification
     */
    public function updateNotification($id, array $data): array
    {
        try {
            $notification = AppNotification::find($id);

            if (! $notification) {
                return [
                    'value' => false,
                    'message' => 'Notification not found',
                ];
            }

            $notification->update($data);

            Log::info('Notification updated successfully', [
                'notification_id' => $id,
                'updated_fields' => array_keys($data),
            ]);

            return [
                'value' => true,
                'data' => $notification,
                'message' => 'Notification updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update notification', [
                'notification_id' => $id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark all notifications as read for authenticated user
     */
    public function markAllNotificationsAsRead(): array
    {
        try {
            $doctorId = auth()->id();
            $updatedCount = AppNotification::where('doctor_id', $doctorId)
                ->where('read', false)
                ->update(['read' => true]);

            Log::info('All notifications marked as read', [
                'doctor_id' => $doctorId,
                'updated_count' => $updatedCount,
            ]);

            return [
                'value' => true,
                'message' => 'All notifications marked as read',
                'updated_count' => $updatedCount,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'doctor_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($id): array
    {
        try {
            $notification = AppNotification::find($id);

            if (! $notification) {
                return [
                    'value' => false,
                    'message' => 'Notification not found',
                ];
            }

            $notification->delete();

            Log::info('Notification deleted successfully', [
                'notification_id' => $id,
            ]);

            return [
                'value' => true,
                'message' => 'Notification deleted successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'notification_id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transform notifications for complex display format
     */
    private function transformNotifications($notifications)
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

            // Use eager loaded typeDoctor data
            $typeDoctor = $notification->typeDoctor ?? (object) [
                'id' => null,
                'name' => null,
                'lname' => null,
                'workingplace' => null,
                'image' => null,
                'isSyndicateCardRequired' => null,
            ];

            return [
                'id' => $notification->id,
                'read' => $notification->read,
                'content' => $notification->content,
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
}
