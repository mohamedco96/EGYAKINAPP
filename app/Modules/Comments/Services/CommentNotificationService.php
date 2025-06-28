<?php

namespace App\Modules\Comments\Services;

use App\Models\Comment;
use App\Modules\Patients\Models\Patients;
use App\Modules\Notifications\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentNotificationService
{
    /**
     * Handle comment notification logic
     *
     * @param Comment $comment
     * @param Patients $patient
     * @param int $doctorId
     * @return void
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
            Log::error('Error handling comment notification: ' . $e->getMessage());
            // Don't throw exception to prevent breaking comment creation
        }
    }

    /**
     * Create notification for new comment
     *
     * @param Comment $comment
     * @param int $patientDoctorId
     * @param int $commentingDoctorId
     * @return void
     */
    private function createCommentNotification(Comment $comment, int $patientDoctorId, int $commentingDoctorId): void
    {
        try {
            AppNotification::create([
                'content' => 'New comment was created',
                'read' => false,
                'type' => 'Comment',
                'patient_id' => $comment->patient_id,
                'doctor_id' => $patientDoctorId,
                'type_id' => $comment->id,
                'type_doctor_id' => $commentingDoctorId,
            ]);

            Log::info('Comment notification created', [
                'comment_id' => $comment->id,
                'patient_id' => $comment->patient_id,
                'notified_doctor_id' => $patientDoctorId,
                'commenting_doctor_id' => $commentingDoctorId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating comment notification: ' . $e->getMessage());
        }
    }
}
