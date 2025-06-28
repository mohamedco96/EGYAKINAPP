<?php

namespace App\Modules\Consultations\Services;

use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Consultations\Models\Consultation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ConsultationNotificationService
{
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
            AppNotification::create([
                'doctor_id' => $doctorId,
                'type' => 'Consultation',
                'type_id' => $consultation->id,
                'content' => 'Dr. ' . $user->name . ' is seeking your advice for his patient',
                'type_doctor_id' => Auth::id(),
                'patient_id' => $patientId
            ]);
        }

        $title = 'New consultation request was created 📣';
        $body = 'Dr. ' . $user->name . ' is seeking your advice for his patient';
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
        AppNotification::create([
            'doctor_id' => $doctorId,
            'type' => 'Consultation',
            'type_id' => $consultationId,
            'content' => 'Dr. ' . $user->name . ' has replied to your consultation request. 📩',
            'type_doctor_id' => $user->id,
            'patient_id' => $patientId
        ]);

        // Prepare and send push notifications to relevant doctors
        $title = 'New Reply on Consultation Request 🔔';
        $body = 'Dr. ' . $user->name . ' has replied to your consultation request. 📩';
        $tokens = FcmToken::whereIn('doctor_id', [$doctorId])
            ->pluck('token')
            ->toArray();

        // Send push notifications
        $this->notificationService->sendPushNotification($title, $body, $tokens);
    }
}
