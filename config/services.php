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

    'arkesel' => [
        // Both values are also storable in the `platform_settings` table, which
        // is what the admin settings screen writes. SmsService prefers the
        // platform setting when present and falls back to these.
        'api_key' => env('ARKESEL_API_KEY'),
        'sender_id' => env('ARKESEL_SENDER_ID', 'Parcelman'),

        // When true, a failed SMS send still leaves a valid OTP in place and
        // writes the code to the application log, so sign-in works without a
        // working SMS account. Enabled by default outside production; force it
        // off with SMS_OTP_LOG_FALLBACK=false. Never enable in production —
        // it writes login codes to the log.
        'log_fallback' => env(
            'SMS_OTP_LOG_FALLBACK',
            env('APP_ENV', 'production') !== 'production'
        ),
    ],

];
