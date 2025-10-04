<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Notifications\Models\AppNotification;
use App\Services\LocalizedNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocalizedNotificationController extends Controller
{
    protected $localizedNotificationService;

    public function __construct(LocalizedNotificationService $localizedNotificationService)
    {
        $this->localizedNotificationService = $localizedNotificationService;
    }

    /**
     * Get all notifications with localized content
     */
    public function getAllNotifications(Request $request): JsonResponse
    {
        try {
            $locale = $request->get('locale', Auth::user()->locale ?? app()->getLocale());

            $result = $this->localizedNotificationService->getAllNotifications($locale);

            return response()->json([
                'success' => true,
                'message' => __('api.notifications_retrieved_successfully'),
                'locale' => $locale,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.failed_to_fetch_notifications'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get new (unread) notifications with localized content
     */
    public function getNewNotifications(Request $request): JsonResponse
    {
        try {
            $locale = $request->get('locale', Auth::user()->locale ?? app()->getLocale());

            $result = $this->localizedNotificationService->getNewNotifications($locale);

            return response()->json([
                'success' => true,
                'message' => __('api.new_notifications_retrieved_successfully'),
                'locale' => $locale,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.failed_to_fetch_new_notifications'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        try {
            $success = $this->localizedNotificationService->markAsRead($id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => __('api.notification_marked_as_read'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('api.notification_not_found'),
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.failed_to_mark_notification_as_read'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $success = $this->localizedNotificationService->markAllAsRead();

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => __('api.all_notifications_marked_as_read'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('api.no_notifications_to_mark'),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.failed_to_mark_all_notifications_as_read'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test creating a localized notification
     */
    public function testCreateLocalizedNotification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Create a test localized notification
            $notification = AppNotification::createLocalized([
                'doctor_id' => $user->id,
                'type' => 'Test',
                'localization_key' => 'api.clean_notification_post_liked',
                'localization_params' => ['name' => 'Dr. Test User'],
                'type_doctor_id' => $user->id,
            ]);

            // Get the localized content in different languages
            $englishContent = $notification->getLocalizedContent('en');
            $arabicContent = $notification->getLocalizedContent('ar');

            return response()->json([
                'success' => true,
                'message' => __('api.test_localized_notification_created'),
                'data' => [
                    'notification_id' => $notification->id,
                    'original_content' => $notification->content,
                    'english_content' => $englishContent,
                    'arabic_content' => $arabicContent,
                    'localization_key' => $notification->localization_key,
                    'localization_params' => $notification->localization_params,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.failed_to_create_test_notification'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
