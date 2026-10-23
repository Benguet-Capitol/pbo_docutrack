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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // HRIS API (App\Services\HrisClient): called from the server so the key stays out of the built JavaScript
    'hris' => [
        'url' => env('HRIS_URL', 'http://192.168.2.26/api/v1'),
        'key' => env('HRIS_KEY'),
        'timeout' => (int) env('HRIS_TIMEOUT', 15),
        'employees_endpoint' => env('HRIS_EMPLOYEES_ENDPOINT', 'getEmployees'),
    ],

    // Shared Records API (/api/v1/records, read-only) for other PBO systems such as pbo-registryV2.
    // RECORDS_API_KEYS: comma-separated keys, one per client app, sent as the X-API-Key header.
    'records_api' => [
        'keys' => array_values(array_filter(array_map('trim', explode(',', (string) env('RECORDS_API_KEYS', ''))))),
    ],

];
