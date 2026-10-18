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
        'key' => env('RESEND_KEY', env('RESEND_API_KEY')),
        'from' => env('RESEND_FROM', env('MAIL_FROM_ADDRESS')),
        'admin_alert_email' => env('RESEND_ADMIN_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
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

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],

    'forex' => [
        'driver' => env('FOREX_DRIVER', 'nrb'),
        'key' => env('FOREX_API_KEY'),
        // exchangerate-api.com v6; {key} is replaced by FOREX_API_KEY. Without a key the free open endpoint is used.
        'url' => env('FOREX_API_URL', 'https://v6.exchangerate-api.com/v6/{key}/latest/USD'),
        'open_url' => 'https://open.er-api.com/v6/latest/USD',
        // driver=nrb: official Nepal Rastra Bank USD sell rate, no key needed.
        'nrb_url' => env('FOREX_NRB_URL', 'https://www.nrb.org.np/api/forex/v1/rates'),
        'cache_seconds' => env('FOREX_CACHE_SECONDS', 3600),
    ],

];
