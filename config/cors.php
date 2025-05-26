<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://api.egyakin.com',
        'https://test.egyakin.com',
    ],

    'allowed_origins_patterns' => [
        '/^https:\/\/[a-zA-Z0-9-]+\.egyakin\.com$/',
        '/^https:\/\/[a-zA-Z0-9-]+\.egyakin\.app$/',
    ],

    'allowed_headers' => [
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'X-API-KEY',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-XSS-Protection',
    ],

    'max_age' => 60 * 60 * 24, // 24 hours

    'supports_credentials' => true,

    'preflight_max_age' => 60 * 60 * 24, // 24 hours

    'allowed_credentials' => true,

    'allowed_origin_patterns' => [
        '/^https:\/\/[a-zA-Z0-9-]+\.egyakin\.com$/',
        '/^https:\/\/[a-zA-Z0-9-]+\.egyakin\.app$/',
    ],

];
