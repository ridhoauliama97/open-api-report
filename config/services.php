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

    'gotenberg' => [
        'url' => env('GOTENBERG_URL', 'http://localhost:3000'),
        'timeout' => (int) env('GOTENBERG_TIMEOUT', 300),
        'connect_timeout' => (int) env('GOTENBERG_CONNECT_TIMEOUT', 10),
        'retry_attempts' => (int) env('GOTENBERG_RETRY_ATTEMPTS', 2),
        'max_interactive' => (int) env('GOTENBERG_MAX_INTERACTIVE', 2),
        'max_warm' => (int) env('GOTENBERG_MAX_WARM', 1),
        'max_async' => (int) env('GOTENBERG_MAX_ASYNC', 1),
        'wait_interactive_seconds' => (int) env('GOTENBERG_WAIT_INTERACTIVE_SECONDS', 2),
        'wait_warm_seconds' => (int) env('GOTENBERG_WAIT_WARM_SECONDS', 0),
        'wait_async_seconds' => (int) env('GOTENBERG_WAIT_ASYNC_SECONDS', 5),
        'throttle_retry_after' => (int) env('GOTENBERG_THROTTLE_RETRY_AFTER', 5),
    ],

];
