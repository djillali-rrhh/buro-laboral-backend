<?php

use App\Services\EmailService\EmailService;
use Illuminate\Support\Facades\Route;

// ========================================
// Test de rutas
// ========================================

Route::get('/test-email-bienvenida', function () {
    // SMTP - usando string directamente
    $smtp = new EmailService('smtp');
    $smtp->queue([
        'to' => ['israel.jobs@pm.com'],
        'from' => 'ingenia.soporte@rrhhingenia.com',
        'subject' => 'Correo vía SMTP',
        'html' => '<h1>Hola!</h1><p>Este correo se envía por SMTP.</p>'
    ]);

    // SendGrid - usando string directamente
    $sendgrid = new EmailService('sendgrid');
    $sendgrid->queue(
        [
            'to' => ['israel.jobs@pm.com'],
            'from' => 'ingenia.soporte@rrhhingenia.com',
            'subject' => 'Correo vía SendGrid',
            'html' => '<h1>Hola!</h1><p>Este correo se envía por SendGrid API.</p>'
        ]
    );

    return response()->json(['message' => 'Correos encolados'], 200);
});

Route::get('/test-email-default', function (EmailService $emailService) {
    $emailService->queue([
        'to' => ['israel.jobs@pm.com'],
        'from' => 'ingenia.soporte@rrhhingenia.com',
        'subject' => 'Correo con estrategia por defecto',
        'html' => '<h1>Hola!</h1><p>Este correo usa la estrategia configurada en .env</p>'
    ]);

    return response()->json(['message' => 'Correo encolado'], 200);
});