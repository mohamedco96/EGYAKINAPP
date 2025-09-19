<?php

namespace App\Modules\Contacts\Services;

use App\Notifications\ContactRequestNotification;
use Illuminate\Support\Facades\Auth;

class ContactNotificationService
{
    /**
     * Send contact request notification
     */
    public function sendContactNotification(string $message): void
    {
        // Pass empty array - ContactRequestNotification will automatically use ADMIN_MAIL_LIST from .env
        $emailAddresses = [];
        $user = Auth::user();

        if ($user) {
            $user->notify(new ContactRequestNotification($emailAddresses, $message));
        }
    }
}
