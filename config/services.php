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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'imagekit' => [
        'public_key' => env('IMAGEKIT_PUBLIC_KEY'),
        'private_key' => env('IMAGEKIT_PRIVATE_KEY'),
        'endpoint' => env('IMAGEKIT_URL_ENDPOINT'),
    ],

    'r2' => [
        'access_key' => env('R2_ACCESS_KEY'),
        'secret_key' => env('R2_SECRET_KEY'),
        'endpoint' => env('R2_ENDPOINT'),
        'bucket' => env('R2_BUCKET'),
        'public_url' => env('R2_PUBLIC_URL', 'https://pub-30394ee3d88d40f4b31a80834adc3bd7.r2.dev'),
    ],

    'virustotal' => [
        'api_key' => env('VIRUSTOTAL_API_KEY'),
        'timeout' => env('VIRUSTOTAL_TIMEOUT', 30),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'neon' => [
        'key' => env('NEON_API_KEY'),
        'project' => env('NEON_PROJECT_ID'),
        'parent_id' => env('NEON_PARENT_ID'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'profile' => env('AWS_PROFILE', 'support'),
    ],
];
