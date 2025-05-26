<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SecurityLogService
{
    /**
     * Log authentication events
     */
    public function logAuthEvent(string $event, array $context = [])
    {
        $defaultContext = [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (Auth::check()) {
            $defaultContext['user_id'] = Auth::id();
        }

        Log::channel('security')->warning("Auth: {$event}", array_merge($defaultContext, $context));
    }

    /**
     * Log permission events
     */
    public function logPermissionEvent(string $event, array $context = [])
    {
        $defaultContext = [
            'ip' => request()->ip(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('security')->warning("Permission: {$event}", array_merge($defaultContext, $context));
    }

    /**
     * Log rate limit events
     */
    public function logRateLimitEvent(string $event, array $context = [])
    {
        $defaultContext = [
            'ip' => request()->ip(),
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (Auth::check()) {
            $defaultContext['user_id'] = Auth::id();
        }

        Log::channel('security')->warning("RateLimit: {$event}", array_merge($defaultContext, $context));
    }

    /**
     * Log file upload events
     */
    public function logFileUploadEvent(string $event, array $context = [])
    {
        $defaultContext = [
            'ip' => request()->ip(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('security')->warning("FileUpload: {$event}", array_merge($defaultContext, $context));
    }

    /**
     * Log sensitive data access
     */
    public function logDataAccessEvent(string $event, array $context = [])
    {
        $defaultContext = [
            'ip' => request()->ip(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toIso8601String(),
        ];

        Log::channel('security')->warning("DataAccess: {$event}", array_merge($defaultContext, $context));
    }
}
