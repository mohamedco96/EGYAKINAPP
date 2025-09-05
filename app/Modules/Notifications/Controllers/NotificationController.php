<?php

namespace App\Modules\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Requests\SendNotificationRequest;
use App\Modules\Notifications\Requests\StoreNotificationRequest;
use App\Modules\Notifications\Requests\UpdateNotificationRequest;
use App\Modules\Notifications\Services\FcmTokenService;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    protected $fcmTokenService;

    public function __construct(
        NotificationService $notificationService,
        FcmTokenService $fcmTokenService
    ) {
        $this->notificationService = $notificationService;
        $this->fcmTokenService = $fcmTokenService;
    }

    /**
     * Send old style notification (for backward compatibility)
     */
    public function sendold(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->sendSingleNotification(
                $request->input('token'),
                $request->input('title'),
                $request->input('body')
            );

            return response()->json(['status' => 'Message sent successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to send single notification', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['status' => 'Failed to send message'], 500);
        }
    }

    /**
     * Send a message to all FCM tokens
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        try {
            $result = $this->notificationService->sendToAllTokens(
                $request->validated()['title'],
                $request->validated()['body']
            );

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to all', [
                'error' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            return response()->json(['status' => 'Failed to send message. Please try again later.'], 500);
        }
    }

    /**
     * Send push notification (used by other services)
     */
    public function sendPushNotification($title, $body, $tokens)
    {
        try {
            return $this->notificationService->sendPushNotification($title, $body, $tokens);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'title' => $title,
                'tokens_count' => count($tokens),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'Failed to send message. Please try again later.'], 500);
        }
    }

    /**
     * Send notification to all users with pre-defined message
     */
    public function sendAllPushNotification(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->sendAllPushNotification();

            return response()->json($result, $result['success'] ? 200 : 500);
        } catch (\Exception $e) {
            Log::error('Failed to send notification to all users', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'Failed to send message. Please try again later.'], 500);
        }
    }

    /**
     * Store a newly created FCM token
     */
    public function storeFCM(Request $request): JsonResponse
    {
        try {
            $result = $this->fcmTokenService->storeFcmToken(
                $request->input('token'),
                $request->input('deviceId'),
                $request->input('deviceType'),
                $request->input('appVersion')
            );

            return response()->json($result, $result['value'] ? 201 : 409);
        } catch (\Exception $e) {
            Log::error('Failed to store FCM token', [
                'error' => $e->getMessage(),
                'doctor_id' => auth()->id(),
                'device_id' => $request->input('deviceId'),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to store FCM token. Please try again later.',
            ], 500);
        }
    }

    /**
     * Display a listing of notifications
     */
    public function index(): JsonResponse
    {
        try {
            $result = $this->notificationService->getAllNotifications();

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching notifications', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to fetch notifications',
            ], 500);
        }
    }

    /**
     * Store a newly created notification
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        try {
            $result = $this->notificationService->createNotification($request->validated());

            return response()->json($result, $result['value'] ? 201 : 400);
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to create notification',
            ], 500);
        }
    }

    /**
     * Display the specified notification
     */
    public function show($id = null): JsonResponse
    {
        try {
            if ($id) {
                $result = $this->notificationService->getNotification($id);
            } else {
                // This seems to be used as get all notifications for authenticated user
                $result = $this->notificationService->getUserNotifications();
            }

            return response()->json($result, $result['value'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching notification', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to fetch notification',
            ], 500);
        }
    }

    /**
     * Display new notifications with complex formatting
     */
    public function showNew(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->getNewNotifications();

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Error occurred while fetching new notifications', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to fetch new notifications',
            ], 500);
        }
    }

    /**
     * Update the specified notification
     */
    public function update(UpdateNotificationRequest $request, $id): JsonResponse
    {
        try {
            $result = $this->notificationService->updateNotification($id, $request->validated());

            return response()->json($result, $result['value'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('Failed to update notification', [
                'id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->validated(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to update notification',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $result = $this->notificationService->markAllNotificationsAsRead();

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'error' => $e->getMessage(),
                'doctor_id' => auth()->id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to mark all notifications as read',
            ], 500);
        }
    }

    /**
     * Remove the specified notification
     */
    public function destroy($id): JsonResponse
    {
        try {
            $result = $this->notificationService->deleteNotification($id);

            return response()->json($result, $result['value'] ? 200 : 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to delete notification',
            ], 500);
        }
    }
}
