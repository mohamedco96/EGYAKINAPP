<?php

namespace App\Modules\Notifications\Policies;

use App\Modules\Notifications\Models\AppNotification;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AppNotification $notification): bool
    {
        // Users can only view their own notifications
        return $user->id === $notification->doctor_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AppNotification $notification): bool
    {
        // Users can only update their own notifications (mainly for marking as read)
        return $user->id === $notification->doctor_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AppNotification $notification): bool
    {
        // Users can only delete their own notifications
        return $user->id === $notification->doctor_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AppNotification $notification): bool
    {
        // Users can only restore their own notifications
        return $user->id === $notification->doctor_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AppNotification $notification): bool
    {
        // Only admins can force delete notifications
        return $user->hasRole(['admin']);
    }

    /**
     * Determine whether the user can manage FCM tokens.
     */
    public function manageFcmTokens(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can send push notifications.
     */
    public function sendPushNotifications(User $user): bool
    {
        // Allow doctors to send notifications to patients, admins can send to anyone
        return $user->hasRole(['admin', 'doctor', 'tester']);
    }

    /**
     * Determine whether the user can send bulk notifications.
     */
    public function sendBulkNotifications(User $user): bool
    {
        // Only admins and testers can send bulk notifications
        return $user->hasRole(['admin', 'tester']);
    }
}
