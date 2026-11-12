<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'resend' => [
        'key' => env('RESEND_KEY'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'demo_api' => [
        'token' => env('API_TOKEN'),
    ],
    'external_api' => [
        'url' => env('EXTERNAL_API_URL', 'https://jsonplaceholder.typicode.com/posts'),
        'timeout' => (int) env('EXTERNAL_API_TIMEOUT', 5),
        'retry' => (int) env('EXTERNAL_API_RETRY', 2),
    ],
];