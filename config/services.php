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

    'google_vision' => [
        // Server-side label reading. The on-device ML Kit recogniser is a
        // printed-text model and returns fragments from handwritten labels, so
        // the app falls back to this when it cannot find a phone number.
        //
        // The key is also storable in the `platform_settings` table (as
        // `google_vision_api_key`), which is what the admin settings screen
        // writes; GoogleVisionLabelExtractor prefers that and falls back here.
        // An API key is used rather than a service account so nothing beyond a
        // single value has to be placed on the server.
        'key' => env('GOOGLE_VISION_API_KEY'),

        'endpoint' => 'https://vision.googleapis.com/v1/images:annotate',

        // Vision accepts inline base64 up to 20MB, but the app already
        // compresses to under 2MB and anything much larger is a mistake worth
        // refusing before paying to upload it.
        'max_bytes' => (int) env('GOOGLE_VISION_MAX_BYTES', 6 * 1024 * 1024),

        'timeout' => (int) env('GOOGLE_VISION_TIMEOUT', 25),
    ],

];
