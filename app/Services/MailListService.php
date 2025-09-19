<?php

namespace App\Services;

class MailListService
{
    /**
     * Get parsed mail list from configuration
     *
     * @param  string  $configKey  The configuration key (e.g., 'admin_mail_list')
     * @return array Array of email addresses
     */
    public static function getMailList(string $configKey = 'admin_mail_list'): array
    {
        $mailList = config("mail.{$configKey}");

        if (empty($mailList)) {
            return [];
        }

        // If it's already an array, return it
        if (is_array($mailList)) {
            return array_filter($mailList, function ($email) {
                return ! empty($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            });
        }

        // If it's a string, split by comma and clean up
        $emails = explode(',', $mailList);
        $cleanEmails = [];

        foreach ($emails as $email) {
            $email = trim($email);
            if (! empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cleanEmails[] = $email;
            }
        }

        return $cleanEmails;
    }

    /**
     * Get admin mail list for all system notifications
     * (Daily Reports, Weekly Summaries, Contact Requests, etc.)
     *
     * @return array Array of admin email addresses
     */
    public static function getAdminMailList(): array
    {
        return self::getMailList('admin_mail_list');
    }

    /**
     * Get admin mail list for daily reports (alias for consistency)
     *
     * @return array Array of email addresses for daily reports
     */
    public static function getDailyReportMailList(): array
    {
        return self::getAdminMailList();
    }

    /**
     * Get admin mail list for weekly summaries (alias for consistency)
     *
     * @return array Array of email addresses for weekly summaries
     */
    public static function getWeeklyReportMailList(): array
    {
        return self::getAdminMailList();
    }

    /**
     * Format email list for Brevo API (single email with multiple recipients)
     *
     * @param  array  $emails  Array of email addresses
     * @return array Formatted for Brevo API
     */
    public static function formatForBrevoApi(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $recipients = [];
        foreach ($emails as $email) {
            $recipients[] = ['email' => $email];
        }

        return $recipients;
    }

    /**
     * Get the primary email (first in the list) for single recipient scenarios
     *
     * @param  string  $configKey  The configuration key
     * @return string|null Primary email address
     */
    public static function getPrimaryEmail(string $configKey = 'admin_mail_list'): ?string
    {
        $emails = self::getMailList($configKey);

        return ! empty($emails) ? $emails[0] : null;
    }
}
