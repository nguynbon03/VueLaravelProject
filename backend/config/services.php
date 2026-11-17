<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'webhooks' => [
        'sepay' => env('WEBHOOK_SEPAY_SECRET', 'sepay-cleancommerce-webhook-secret-2026'),
        'stripe' => env('WEBHOOK_STRIPE_SECRET', 'stripe-cleancommerce-webhook-secret-2026'),
    ],

    'vietqr' => [
        'bank_code' => env('VIETQR_BANK_CODE', 'MB'),
        'bank_name' => env('VIETQR_BANK_NAME', 'MB Bank'),
        'account_number' => env('VIETQR_ACCOUNT_NUMBER', '0333666999'),
        'account_holder' => env('VIETQR_ACCOUNT_HOLDER', 'CONG TY CLEAN COMMERCE'),
    ],

];
