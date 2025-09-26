<?php

namespace App\Traits;

use App\Models\User;

trait FormatsUserName
{
    /**
     * Format user name with "Dr." prefix if verified
     *
     * @param  User|object  $user  User object with name, lname, and isSyndicateCardRequired
     * @return string Formatted name
     */
    protected function formatUserName($user): string
    {
        if (! $user || ! isset($user->name)) {
            return '';
        }

        $fullName = trim($user->name.' '.($user->lname ?? ''));

        // Add "Dr." prefix only for verified users
        if (isset($user->isSyndicateCardRequired) && $user->isSyndicateCardRequired === 'Verified') {
            return 'Dr. '.$fullName;
        }

        return $fullName;
    }

    /**
     * Format user name with "Dr." prefix if verified (static version)
     *
     * @param  User|object  $user  User object with name, lname, and isSyndicateCardRequired
     * @return string Formatted name
     */
    public static function getFormattedUserName($user): string
    {
        if (! $user || ! isset($user->name)) {
            return '';
        }

        $fullName = trim($user->name.' '.($user->lname ?? ''));

        // Add "Dr." prefix only for verified users
        if (isset($user->isSyndicateCardRequired) && $user->isSyndicateCardRequired === 'Verified') {
            return 'Dr. '.$fullName;
        }

        return $fullName;
    }
}
