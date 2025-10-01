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

        // Add "Dr." prefix only for verified users, but avoid duplication
        if (isset($user->isSyndicateCardRequired) && $user->isSyndicateCardRequired === 'Verified') {
            // Check if the name already starts with "Dr." or Arabic "د." to avoid duplication
            if (! self::hasDoctoralPrefix($fullName)) {
                return 'Dr. '.$fullName;
            }
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

        // Add "Dr." prefix only for verified users, but avoid duplication
        if (isset($user->isSyndicateCardRequired) && $user->isSyndicateCardRequired === 'Verified') {
            // Check if the name already starts with "Dr." or Arabic "د." to avoid duplication
            if (! self::hasDoctoralPrefix($fullName)) {
                return 'Dr. '.$fullName;
            }
        }

        return $fullName;
    }

    /**
     * Check if a name already has a doctoral prefix
     */
    public static function hasDoctoralPrefix(string $name): bool
    {
        $name = trim($name);

        // Check for English "Dr." prefix (case insensitive) - with or without space
        if (preg_match('/^dr\.?\s*/i', $name)) {
            return true;
        }

        // Check for Arabic "د." prefix - with or without space
        if (preg_match('/^د\.?\s*/', $name)) {
            return true;
        }

        // Check for "Doctor" prefix (case insensitive) - must have space or end of string
        if (preg_match('/^doctor(\s+|$)/i', $name)) {
            return true;
        }

        return false;
    }
}
