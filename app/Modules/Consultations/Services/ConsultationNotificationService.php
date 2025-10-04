<?php

namespace App\Modules\Consultations\Services;

use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use App\Traits\FormatsUserName;
use Illuminate\Support\Facades\Auth;

class ConsultationNotificationService
{
    use FormatsUserName;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send notifications when a consultation is created
     */
    public function sendConsultationCreatedNotifications(Consultation $consultation, array $doctors, int $patientId): void
    {
        $user = Auth::user();

        foreach ($doctors as $doctorId) {
            AppNotification::createLocalized([
                'doctor_id' => $doctorId,
                'type' => 'Consultation',
                'type_id' => $consultation->id,
                'localization_key' => 'api.clean_notification_consultation_request',
                'localization_params' => ['name' => $this->formatUserName($user)],
                'type_doctor_id' => Auth::id(),
                'patient_id' => $patientId,
            ]);
        }

        $title = __('api.new_consultation_request_created');
        $body = __('api.clean_doctor_seeking_advice', ['name' => $this->formatUserName($user)]);
        $tokens = FcmToken::whereIn('doctor_id', $doctors)
            ->pluck('token')
            ->toArray();

        $this->notificationService->sendPushNotification($title, $body, $tokens);
    }

    /**
     * Send notification when a consultation reply is submitted
     */
    public function sendConsultationReplyNotification(User $user, int $doctorId, int $consultationId, ?int $patientId): void
    {
        // Create a new notification for the doctor who created the consultation request
        AppNotification::createLocalized([
            'doctor_id' => $doctorId,
            'type' => 'Consultation',
            'type_id' => $consultationId,
            'localization_key' => 'api.clean_notification_consultation_reply',
            'localization_params' => ['name' => $user->name],
            'type_doctor_id' => $user->id,
            'patient_id' => $patientId,
        ]);

        // Prepare and send push notifications to relevant doctors
        $title = __('api.new_reply_on_consultation');
        $body = __('api.clean_doctor_replied_to_consultation', ['name' => $this->formatUserName($user)]);
        $tokens = FcmToken::whereIn('doctor_id', [$doctorId])
            ->pluck('token')
            ->toArray();

        // Send push notifications
        $this->notificationService->sendPushNotification($title, $body, $tokens);
    }
}
