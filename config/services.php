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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'poder_judicial' => [
        'base_uri' => env('PODER_JUDICIAL_API_URL'),
        'secret' => env('PODER_JUDICIAL_API_KEY'),
    ],

    'ingenia' => [
        'base_uri' => env('INGENIA_API_URL'), 
    ],
  
    'buro_de_ingresos' => [
        'api_key' => env('BURO_INGRESOS_API_KEY'),
        'webhook_key' => env('BURO_INGRESOS_WEBHOOK_KEY'),
        'base_url' => env('BURO_INGRESOS_API_BASE_URL'),
        'sandbox_mode' => env('BURO_INGRESOS_SANDBOX_MODE', true),
    ],
    
    'sendgrid' => [
        'key' => env('SENDGRID_API_KEY'),
        'sandbox_mode' => env('SENDGRID_SANDBOX_MODE', true),
    ],
];