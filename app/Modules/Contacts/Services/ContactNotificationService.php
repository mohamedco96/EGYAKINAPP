<?php

namespace App\Modules\Contacts\Services;

use App\Notifications\ContactRequestNotification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ContactNotificationService
{
    /**
     * Send contact request notification
     */
    public function sendContactNotification(string $message): void
    {
        $emailAddresses = ['mostafa_abdelsalam@egyakin.com', 'Darsh1980@mans.edu.eg'];
        $user = Auth::user();
        
        if ($user) {
            $user->notify(new ContactRequestNotification($emailAddresses, $message));
        }
    }
}
