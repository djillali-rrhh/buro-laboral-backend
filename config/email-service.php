<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Service Driver
    |--------------------------------------------------------------------------
    |
    | Estrategias disponibles: "smtp", "sendgrid"
    |
    */
    'driver' => env('MAIL_EMAIL_SERVICE_DRIVER', 'smtp'),

    /*
    |--------------------------------------------------------------------------
    | Drivers disponibles
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'smtp' => \App\Services\EmailService\Strategies\SmtpEmailStrategy::class,
        'sendgrid' => \App\Services\EmailService\Strategies\SendGridEmailStrategy::class,
    ],
];