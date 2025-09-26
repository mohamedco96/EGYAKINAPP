<?php

namespace App\Modules\Comments\Services;

use App\Modules\Comments\Models\Comment;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Patients\Models\Patients;
use App\Services\NotificationService;
use App\Traits\FormatsUserName;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentNotificationService
{
    use FormatsUserName;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle comment notification logic
     */
    public function handleCommentNotification(Comment $comment, Patients $patient, int $doctorId): void
    {
        try {
            // Retrieve the patient's doctor ID and cast to integer
            $patientDoctorId = (int) $patient->doctor_id;

            // Log the doctor IDs for debugging
            Log::debug('Authenticated Doctor ID:', ['doctor_id' => $doctorId]);
            Log::debug('Patient Doctor ID:', ['patient_doctor_id' => $patientDoctorId]);

            // Check if the authenticated user is not the patient's doctor
            if ($patientDoctorId !== $doctorId) {
                $this->createCommentNotification($comment, $patientDoctorId, $doctorId);
            } else {
                // Log that no notification was sent
                Log::debug('No notification sent as the authenticated doctor is the same as the patient\'s doctor.');
            }
        } catch (\Exception $e) {
            Log::error('Error handling comment notification: '.$e->getMessage());
            // Don't throw exception to prevent breaking comment creation
        }
    }

    /**
     * Create notification for new comment
     */
    private function createCommentNotification(Comment $comment, int $patientDoctorId, int $commentingDoctorId): void
    {
        try {
            AppNotification::createLocalized([
                'localization_key' => 'api.notification_new_comment',
                'read' => false,
                'type' => 'Comment',
                'patient_id' => $comment->patient_id,
                'doctor_id' => $patientDoctorId,
                'type_id' => $comment->id,
                'type_doctor_id' => $commentingDoctorId,
            ]);

            // Send push notification
            $tokens = FcmToken::where('doctor_id', $patientDoctorId)
                ->pluck('token')
                ->toArray();

            if (! empty($tokens)) {
                $commentingUser = Auth::user();
                $this->notificationService->sendPushNotification(
                    __('api.new_patient_comment'),
                    __('api.doctor_commented_on_patient', ['name' => ucfirst($this->formatUserName($commentingUser))]),
                    $tokens
                );
            }

            Log::info('Comment notification created', [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'notified_doctor_id' => $patientDoctorId,
                'commenting_doctor_id' => $commentingDoctorId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating comment notification: '.$e->getMessage());
        }
    }
}
