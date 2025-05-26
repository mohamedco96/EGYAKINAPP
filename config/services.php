<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // 'mailgun' => [
    //     'domain' => env('MAILGUN_DOMAIN'),
    //     'secret' => env('MAILGUN_SECRET'),
    //     'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    //     'scheme' => 'https',
    // ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'https://api.eu.mailgun.net'),
        'scheme' => env('MAILGUN_SCHEME', 'https'),
        'from' => [
            'name' => env('MAILGUN_FROM_NAME', 'OTP Verification'),
            'address' => env('MAILGUN_FROM_ADDRESS', 'verification@egyakin.com'),
        ],
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
        'endpoint' => env('POSTMARK_ENDPOINT', 'https://api.postmarkapp.com'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'endpoint' => env('AWS_ENDPOINT', 'https://email.us-east-1.amazonaws.com'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'database_url' => env('FIREBASE_DATABASE_URL'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],
];
