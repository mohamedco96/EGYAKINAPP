<?php

namespace App\Services;

use App\Modules\Notifications\Models\AppNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LocalizedNotificationService
{
    /**
     * Get all notifications for the authenticated user with localized content
     */
    public function getAllNotifications(?string $locale = null): array
    {
        $user = Auth::user();
        if (! $user) {
            return [
                'value' => false,
                'unreadCount' => 0,
                'data' => [],
            ];
        }

        // Set locale if provided
        $originalLocale = null;
        if ($locale && $locale !== App::getLocale()) {
            $originalLocale = App::getLocale();
            App::setLocale($locale);
        }

        try {
            $notifications = AppNotification::where('doctor_id', $user->id)
                ->with(['patient', 'doctor', 'typeDoctor'])
                ->latest()
                ->get();

            $unreadCount = $notifications->where('read', false)->count();

            // Transform notifications with localized content
            $localizedNotifications = $notifications->map(function ($notification) {
                $notificationArray = $notification->toArray();

                // Add localized content
                $notificationArray['localized_content'] = $notification->getLocalizedContent();

                return $notificationArray;
            });

            return [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $localizedNotifications,
            ];
        } finally {
            // Restore original locale if it was changed
            if ($originalLocale) {
                App::setLocale($originalLocale);
            }
        }
    }

    /**
     * Get new notifications count for the authenticated user
     */
    public function getNewNotifications(?string $locale = null): array
    {
        $user = Auth::user();
        if (! $user) {
            return [
                'value' => false,
                'unreadCount' => 0,
                'data' => [],
            ];
        }

        // Set locale if provided
        $originalLocale = null;
        if ($locale && $locale !== App::getLocale()) {
            $originalLocale = App::getLocale();
            App::setLocale($locale);
        }

        try {
            $unreadNotifications = AppNotification::where('doctor_id', $user->id)
                ->where('read', false)
                ->with(['patient', 'doctor', 'typeDoctor'])
                ->latest()
                ->get();

            $unreadCount = $unreadNotifications->count();

            // Transform notifications with localized content
            $localizedNotifications = $unreadNotifications->map(function ($notification) {
                $notificationArray = $notification->toArray();

                // Add localized content
                $notificationArray['localized_content'] = $notification->getLocalizedContent();

                return $notificationArray;
            });

            return [
                'value' => true,
                'unreadCount' => $unreadCount,
                'data' => $localizedNotifications,
            ];
        } finally {
            // Restore original locale if it was changed
            if ($originalLocale) {
                App::setLocale($originalLocale);
            }
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $notification = AppNotification::where('id', $notificationId)
            ->where('doctor_id', $user->id)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->read = true;

        return $notification->save();
    }

    /**
     * Mark all notifications as read for the authenticated user
     */
    public function markAllAsRead(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return AppNotification::where('doctor_id', $user->id)
            ->where('read', false)
            ->update(['read' => true]) !== false;
    }
}
