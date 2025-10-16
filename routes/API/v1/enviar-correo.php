<?php

use App\Services\EmailService\EmailService;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Route;

// ========================================
// TEST 1: HTML Puro con SendGrid (Payload completo)
// ========================================
Route::get('/test-email-html', function () {
    $sendgrid = new EmailService('sendgrid');
    $sendgrid->queue([
        // Destinatarios
        'to' => [
            'israel.755.sistemas@gmail.com' => 'Israel Martínez',
            'israel.jobs@pm.com' => 'Israel Jobs'
        ],
        'cc' => [
            'israel.martinez4540@gmail.com' => 'Gerente de RRHH'
        ],
        'bcc' => [
            'i66257747@gmail.com' => 'Auditoría'
        ],
        
        // Remitente
        'from' => 'soporte@rrhh-ingenia.com.mx',
        'from_name' => 'Sistema RRHH Ingenia',
        'reply_to' => [
            'atencion@rrhh-ingenia.com.mx' => 'Atención al Cliente'
        ],
        
        // Contenido
        'subject' => '✅ Test HTML Puro - Payload Completo',
        'html' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <h1 style="color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px;">
                        🎯 Test de Email con HTML Puro
                    </h1>
                    
                    <div style="margin: 20px 0;">
                        <h2 style="color: #34495e;">Datos del Payload:</h2>
                        <ul style="line-height: 1.8;">
                            <li><strong>Estrategia:</strong> SendGrid</li>
                            <li><strong>TO:</strong> 2 destinatarios principales</li>
                            <li><strong>CC:</strong> 1 copia visible</li>
                            <li><strong>BCC:</strong> 1 copia oculta</li>
                            <li><strong>Reply-To:</strong> Configurado</li>
                            <li><strong>Prioridad:</strong> Alta (1)</li>
                            <li><strong>Headers:</strong> Personalizados</li>
                            <li><strong>Adjuntos:</strong> 1 archivo PDF</li>
                        </ul>
                    </div>
                    
                    <div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin: 20px 0;">
                        <p style="margin: 0; color: #2e7d32;">
                            ✅ Este email demuestra el uso de HTML puro con todos los campos del payload unificado.
                        </p>
                    </div>
                    
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
                        <p style="color: #7f8c8d; font-size: 12px; margin: 0;">
                            Email enviado desde el Sistema RRHH Ingenia<br>
                            Contexto: test-html | ID: 1001
                        </p>
                    </div>
                </div>
            </div>
        ',
        
        // Opciones adicionales
        'priority' => 1,  // Alta prioridad
        'headers' => [
            'X-Email-Type' => 'test',
            'X-Environment' => 'development',
            'X-Test-ID' => 'html-001'
        ],
        
        // Adjuntos
        'attachments' => [
            [
                'path' => storage_path('test.pdf'),
                'name' => 'documento-test.pdf',
                'type' => 'application/pdf',
                'disposition' => 'attachment'
            ]
        ],
    ]);

    return response()->json([
        'message' => 'Email HTML encolado con payload completo',
        'strategy' => 'sendgrid',
        'fields' => [
            'to' => 2,
            'cc' => 1,
            'bcc' => 1,
            'attachments' => 1,
            'priority' => 'high',
            'custom_headers' => 3
        ]
    ], 200);
});

// ========================================
// TEST 2: Vista Blade con SMTP (Payload completo)
// ========================================
Route::get('/test-email-view', function () {
    $smtp = new EmailService('smtp');
    $smtp->queue([
        // Destinatarios
        'to' => [
            'israel.martinez4540@proton.me' => 'Israel Martínez'
        ],
        'cc' => [
            'israel.755.sistemas@gmail.com' => 'Supervisor',
            'israel.jobs@pm.com' => 'Gerente'
        ],
        'bcc' => [
            'i66257747@gmail.com' => 'Administrador Sistema'
        ],
        
        // Remitente
        'from' => 'soporteingenia@rrhhingenia.com',
        'from_name' => 'Plataforma RRHH Ingenia',
        'reply_to' => [
            'soporte.tecnico@rrhhingenia.com' => 'Soporte Técnico'
        ],
        
        // Contenido con Vista
        'subject' => '🎨 Test con Vista Blade - Todos los Campos',
        'view' => 'emails.welcome',
        'view_data' => [
            'name' => 'Israel Martínez',
            'verificationUrl' => 'https://rrhh-ingenia.com.mx/verify/abc123xyz',
            'additionalInfo' => 'Este email fue generado usando una vista Blade de Laravel con todos los parámetros del payload.',
            'features' => [
                'Múltiples destinatarios (TO, CC, BCC)',
                'Reply-To personalizado',
                'Headers customizados',
                'Prioridad configurada',
                'Adjuntos soportados',
                'Metadata de negocio'
            ]
        ],
        
        // Opciones adicionales
        'priority' => 2,  // Prioridad alta-media
        'headers' => [
            'X-Email-Type' => 'welcome',
            'X-Template' => 'emails.welcome',
            'X-Campaign-ID' => 'WEL-2025-001',
            'X-User-Segment' => 'new-employees'
        ],
        
        // Adjuntos
        'attachments' => [
            [
                'path' => storage_path('test.pdf'),
                'name' => 'manual-bienvenida.pdf',
                'type' => 'application/pdf',
                'disposition' => 'attachment'
            ]
        ],
    ]);

    return response()->json([
        'message' => 'Email con Vista Blade encolado',
        'strategy' => 'smtp',
        'template' => 'emails.welcome',
        'fields' => [
            'to' => 1,
            'cc' => 2,
            'bcc' => 1,
            'attachments' => 1,
            'priority' => 'high-medium',
            'custom_headers' => 4,
            'view_variables' => 3
        ]
    ], 200);
});

// ========================================
// TEST 3: Mailable con SMTP (Payload completo)
// ========================================
Route::get('/test-email-mailable', function () {
    $smtp = new EmailService('smtp');
    $smtp->queue([
        // Destinatarios
        'to' => [
            'israel.jobs@pm.com' => 'Israel Jobs',
            'israel.755.sistemas@gmail.com' => 'Israel Sistemas'
        ],
        'cc' => [
            'israel.martinez4540@gmail.com' => 'Recursos Humanos'
        ],
        'bcc' => [
            'i66257747@gmail.com' => 'Director General',
            'israel.martinez4540@proton.me' => 'Auditoría Interna'
        ],
        
        // Remitente
        'from' => 'soporteingenia@rrhhingenia.com',
        'from_name' => 'Sistema Automatizado RRHH',
        'reply_to' => [
            'noreply@rrhhingenia.com' => 'No Responder (Automatizado)'
        ],
        
        // Mailable
        'mailable' => new WelcomeEmail(
            userName: 'Israel Martínez González',
            verificationUrl: 'https://rrhh-ingenia.com.mx/verify/token-abc-123-xyz-789'
        ),
        
        // Opciones adicionales
        'priority' => 1,  // Máxima prioridad
        'headers' => [
            'X-Email-Type' => 'mailable',
            'X-Mailable-Class' => 'WelcomeEmail',
            'X-Auto-Generated' => 'true',
            'X-Notification-Type' => 'account-activation',
            'X-Business-Unit' => 'human-resources'
        ],
    ]);

    return response()->json([
        'message' => 'Mailable encolado con payload completo',
        'strategy' => 'smtp',
        'mailable' => 'WelcomeEmail',
        'fields' => [
            'to' => 2,
            'cc' => 1,
            'bcc' => 2,
            'attachments' => 0,
            'priority' => 'highest',
            'custom_headers' => 5,
            'mailable_params' => 2
        ],
        'note' => 'El Mailable puede incluir sus propios adjuntos internamente'
    ], 200);
});