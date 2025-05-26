<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enable XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy
        $csp = "default-src 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://api.egyakin.com https://test.egyakin.com; ".
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ".
               "img-src 'self' data: https:; ".
               "font-src 'self' https://fonts.gstatic.com; ".
               "connect-src 'self' https://api.egyakin.com https://test.egyakin.com; ".
               "frame-ancestors 'none'; ".
               "form-action 'self'; ".
               "base-uri 'self'; ".
               "object-src 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        // Permissions Policy
        $permissionsPolicy = 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), '.
                           'magnetometer=(), microphone=(), payment=(), usb=()';
        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // Force HTTPS
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Prevent caching of sensitive data
        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
