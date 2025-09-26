<?php

namespace App\Traits;

use App\Modules\Notifications\Models\AppNotification;
use Illuminate\Support\Facades\Log;

trait NotificationCleanup
{
    /**
     * Clean up notifications related to a post
     *
     * @return int Number of deleted notifications
     */
    protected function cleanupPostNotifications(int $postId): int
    {
        $deletedCount = AppNotification::where(function ($query) {
            $query->where('type', 'PostLike')
                ->orWhere('type', 'PostComment')
                ->orWhere('type', 'CommentLike')
                ->orWhere('type', 'Post')
                ->orWhere('type', 'post_like')
                ->orWhere('type', 'post_comment')
                ->orWhere('type', 'comment_like')
                ->orWhere('type', 'feed_post_like')
                ->orWhere('type', 'feed_post_comment');
        })
            ->where('type_id', $postId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} notifications for post ID {$postId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications related to a comment
     *
     * @return int Number of deleted notifications
     */
    protected function cleanupCommentNotifications(int $commentId): int
    {
        $deletedCount = AppNotification::where(function ($query) {
            $query->where('type', 'CommentLike')
                ->orWhere('type', 'comment_like')
                ->orWhere('type', 'CommentReply')
                ->orWhere('type', 'comment_reply')
                ->orWhere('type', 'feed_post_comment_like')
                ->orWhere('type', 'PostComment')
                ->orWhere('type', 'post_comment');
        })
            ->where('type_id', $commentId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} notifications for comment ID {$commentId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications related to a patient
     *
     * @return int Number of deleted notifications
     */
    protected function cleanupPatientNotifications(int $patientId): int
    {
        $deletedCount = AppNotification::where('patient_id', $patientId)->delete();

        Log::info("Cleaned up {$deletedCount} notifications for patient ID {$patientId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications related to a consultation
     *
     * @return int Number of deleted notifications
     */
    protected function cleanupConsultationNotifications(int $consultationId): int
    {
        $deletedCount = AppNotification::where(function ($query) {
            $query->where('type', 'Consultation')
                ->orWhere('type', 'consultation')
                ->orWhere('type', 'consultation_request')
                ->orWhere('type', 'consultation_reply');
        })
            ->where('type_id', $consultationId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} notifications for consultation ID {$consultationId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications related to a group
     *
     * @return int Number of deleted notifications
     */
    protected function cleanupGroupNotifications(int $groupId): int
    {
        $deletedCount = AppNotification::where(function ($query) {
            $query->where('type', 'group_join_request')
                ->orWhere('type', 'group_invitation')
                ->orWhere('type', 'group_member_added')
                ->orWhere('type', 'group_member_removed')
                ->orWhere('type', 'GroupJoinRequest')
                ->orWhere('type', 'GroupInvitation');
        })
            ->where('type_id', $groupId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} notifications for group ID {$groupId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications for a specific action type and ID
     *
     * @param  string|array  $types
     * @return int Number of deleted notifications
     */
    protected function cleanupNotificationsByType($types, int $typeId): int
    {
        $types = is_array($types) ? $types : [$types];

        $deletedCount = AppNotification::whereIn('type', $types)
            ->where('type_id', $typeId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} notifications for types: ".implode(', ', $types)." with ID {$typeId}");

        return $deletedCount;
    }

    /**
     * Clean up notifications for a specific doctor's actions
     *
     * @param  string|array  $types
     * @return int Number of deleted notifications
     */
    protected function cleanupDoctorActionNotifications(int $doctorId, $types, ?int $typeId = null): int
    {
        $types = is_array($types) ? $types : [$types];

        $query = AppNotification::whereIn('type', $types)
            ->where('type_doctor_id', $doctorId);

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deletedCount = $query->delete();

        Log::info("Cleaned up {$deletedCount} notifications for doctor ID {$doctorId} actions: ".implode(', ', $types));

        return $deletedCount;
    }

    /**
     * Clean up like notifications when a like is removed
     *
     * @param  string  $likeType  (e.g., 'PostLike', 'CommentLike')
     * @return int Number of deleted notifications
     */
    protected function cleanupLikeNotifications(int $postId, int $doctorId, string $likeType = 'PostLike'): int
    {
        $deletedCount = AppNotification::where('type', $likeType)
            ->where('type_id', $postId)
            ->where('type_doctor_id', $doctorId)
            ->delete();

        Log::info("Cleaned up {$deletedCount} {$likeType} notifications for post ID {$postId} by doctor ID {$doctorId}");

        return $deletedCount;
    }
}
