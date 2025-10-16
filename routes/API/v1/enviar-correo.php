<?php

use App\Services\EmailService\EmailService;
use App\Services\EmailService\Helpers\AttachmentHelper;
use Illuminate\Support\Facades\Route;

// ========================================
// Rutas simples (las que ya tienes)
// ========================================

Route::get('/test-email-bienvenida', function () {
    $emailService = new EmailService();

    $result = $emailService->sendTemplateEmail(
        template: 'bienvenida',
        variables: [
            'nombre' => 'Ulises',
            'fecha' => now()->format('d/m/Y H:i'),
            'url' => 'https://tuapp.com/dashboard'
        ],
        options: [
            'to' => 'test@ejemplo.com',
            'subject' => 'Bienvenido a RRHH INGENIA',
            'triggered_by' => 'test_route'
        ]
    );

    return $result ? '✅ Email enviado! Revisa tu Mailtrap' : '❌ Error al enviar';
});

Route::get('/test-email-notificacion', function () {
    $emailService = new EmailService();

    $result = $emailService->sendTemplateEmail(
        template: 'notificacion',
        variables: [
            'nombre' => 'Ulises',
            'titulo' => 'Tu solicitud fue aprobada',
            'mensaje' => 'Nos complace informarte que tu solicitud de vacaciones del 20 al 25 de octubre ha sido aprobada por tu supervisor.',
            'detalles' => [
                'Periodo' => '20 Oct - 25 Oct 2025',
                'Días solicitados' => '5 días',
                'Aprobado por' => 'Juan Pérez',
                'Fecha de aprobación' => now()->format('d/m/Y')
            ],
            'accion_url' => 'https://tuapp.com/mis-solicitudes',
            'accion_texto' => 'Ver mis solicitudes'
        ],
        options: [
            'to' => 'test@ejemplo.com',
            'cc' => ['supervisor@ejemplo.com'],
            'subject' => '✅ Solicitud de vacaciones aprobada',
            'triggered_by' => 'test_route'
        ]
    );

    return $result ? '✅ Email enviado! Revisa tu Mailtrap' : '❌ Error al enviar';
});

Route::get('/test-email-adjuntos', function () {
    $emailService = new EmailService();

    $result = $emailService->sendTemplateEmail(
        template: 'notificacion',
        variables: [
            'nombre' => 'Ulises',
            'titulo' => 'Documentos de tu solicitud',
            'mensaje' => 'Adjuntamos los documentos relacionados con tu solicitud de vacaciones. Por favor revísalos y confírmalos.',
            'detalles' => [
                'Tipo de documento' => 'Solicitud de vacaciones',
                'Generado el' => now()->format('d/m/Y H:i'),
                'Adjuntos' => '1 archivo PDF'
            ],
            'accion_url' => 'https://tuapp.com/mis-documentos',
            'accion_texto' => 'Ver todos mis documentos'
        ],
        options: [
            'to' => 'test@ejemplo.com',
            'cc' => ['rrhh@ejemplo.com'],
            'subject' => '📎 Documentos adjuntos - Solicitud de vacaciones',
            'triggered_by' => 'test_adjuntos',
            'attachments' => [
                [
                    'path' => public_path('test.pdf'),
                    'name' => 'solicitud-vacaciones.pdf',
                    'mime' => 'application/pdf'
                ]
            ]
        ]
    );

    return $result ? '✅ Email con adjunto enviado! Revisa tu Mailtrap' : '❌ Error al enviar';
});

// ========================================
// 🔥 Nuevas rutas con Context Manager
// ========================================

/**
 * Test 1: Adjunto desde URL con auto-cleanup
 */
Route::get('/test-context-url', function () {
    $emailService = new EmailService();
    
    return AttachmentHelper::withTemp(function ($temp) use ($emailService) {
        
        // Descargar PDF de prueba desde internet
        $attachments = [
            $temp->fromUrl(
                'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'documento-descargado.pdf'
            )
        ];

        $result = $emailService->sendTemplateEmail(
            template: 'notificacion',
            variables: [
                'nombre' => 'Ulises',
                'titulo' => 'Documento descargado automáticamente',
                'mensaje' => 'Este documento fue descargado desde una URL externa y se limpiará automáticamente después del envío.'
            ],
            options: [
                'to' => 'test@ejemplo.com',
                'subject' => '🌐 Adjunto descargado desde URL',
                'attachments' => $attachments
            ]
        );

        // ✨ Al salir de aquí, el archivo temporal se borra automáticamente
        return $result ? '✅ Email enviado! Archivo temp limpiado automáticamente' : '❌ Error';
    });
});

/**
 * Test 2: Múltiples adjuntos desde diferentes fuentes
 */
Route::get('/test-context-multiple', function () {
    $emailService = new EmailService();
    
    return AttachmentHelper::withTemp(function ($temp) use ($emailService) {
        
        $attachments = [
            // Desde URL
            $temp->fromUrl(
                'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'pdf-externo.pdf'
            ),
            
            // Desde archivo local (NO se limpia)
            $temp->fromLocal(
                public_path('test.pdf'),
                'pdf-local.pdf'
            ),
            
            // Desde base64 (ejemplo mínimo)
            $temp->fromBase64(
                'JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBlL0NhdGFsb2cvUGFnZXMgMiAwIFI+PmVuZG9iag==',
                'mini-pdf.pdf'
            )
        ];

        $result = $emailService->sendTemplateEmail(
            template: 'notificacion',
            variables: [
                'nombre' => 'Ulises',
                'titulo' => 'Múltiples adjuntos de diferentes fuentes',
                'mensaje' => 'Este email incluye archivos desde URL, local y base64.',
                'detalles' => [
                    'Total adjuntos' => count($attachments),
                    'Tipos' => 'URL, Local, Base64'
                ]
            ],
            options: [
                'to' => 'test@ejemplo.com',
                'subject' => '📦 Múltiples adjuntos con auto-cleanup',
                'attachments' => $attachments
            ]
        );

        return $result 
            ? '✅ Email enviado con ' . count($attachments) . ' adjuntos! Temps limpiados' 
            : '❌ Error';
    });
});

/**
 * Test 3: Desde base64 (simulando upload desde frontend)
 */
Route::get('/test-context-base64', function () {
    $emailService = new EmailService();
    
    // Simular datos base64 que vendrían del frontend
    $base64Pdf = 'JVBERi0xLjQKJeLjz9MKMyAwIG9iago8PC9UeXBlL0NhdGFsb2cvUGFnZXMgMiAwIFI+PmVuZG9iag==';
    
    return AttachmentHelper::withTemp(function ($temp) use ($emailService, $base64Pdf) {
        
        $attachments = [
            $temp->fromBase64(
                $base64Pdf,
                'documento-base64.pdf',
                'application/pdf'
            )
        ];

        $result = $emailService->sendTemplateEmail(
            template: 'bienvenida',
            variables: [
                'nombre' => 'Ulises',
                'fecha' => now()->format('d/m/Y H:i'),
                'url' => 'https://tuapp.com/dashboard'
            ],
            options: [
                'to' => 'test@ejemplo.com',
                'subject' => '📄 Adjunto desde Base64',
                'attachments' => $attachments
            ]
        );

        return $result ? '✅ Email con base64 enviado! Temp limpiado' : '❌ Error';
    });
});

/**
 * Test 4: Manejo de errores (el archivo se limpia incluso si hay error)
 */
Route::get('/test-context-error-handling', function () {
    $emailService = new EmailService();
    
    try {
        return AttachmentHelper::withTemp(function ($temp) use ($emailService) {
            
            // Descargar archivo
            $attachments = [
                $temp->fromUrl(
                    'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                    'documento.pdf'
                )
            ];

            // Simular un error (email inválido)
            $result = $emailService->sendTemplateEmail(
                template: 'notificacion',
                variables: ['nombre' => 'Test'],
                options: [
                    'to' => 'email-invalido',
                    'subject' => 'Test error',
                    'attachments' => $attachments
                ]
            );

            return '✅ Enviado (no debería llegar aquí)';
            
        });
    } catch (\Exception $e) {
        return '❌ Error capturado: ' . $e->getMessage() . ' (pero el archivo temp se limpió!)';
    }
});

/**
 * Test: Verificar configuración de SendGrid
 */
Route::get('/test-sendgrid-config', function () {
    $sendGridService = new \App\Services\EmailService\SendGridService();
    
    return [
        'sendgrid_enabled' => $sendGridService->isEnabled(),
        'api_key_configured' => !empty(config('services.sendgrid.api_key')),
        'api_key_preview' => config('services.sendgrid.enabled') 
            ? 'SG.****' . substr(config('services.sendgrid.api_key'), -4)
            : 'No configurado',
        'message' => $sendGridService->isEnabled() 
            ? '✅ SendGrid está habilitado y configurado'
            : '⚠️ SendGrid está deshabilitado. Usando Mailtrap/SMTP'
    ];
});

/**
 * Test: Email simple con SendGrid
 */
Route::get('/test-sendgrid-simple', function () {
    $emailService = new EmailService();
    
    $result = $emailService->sendTemplateEmail(
        template: 'bienvenida',
        variables: [
            'nombre' => 'Israel',
            'fecha' => now()->format('d/m/Y H:i'),
            'url' => 'https://tuapp.com/dashboard'
        ],
        options: [
            'to' => 'israel.jobs@pm.com',
            'from' => 'soporte.ingenia@rrhhingenia.com',
            'subject' => '🚀 Test SendGrid API - Bienvenida',
            'triggered_by' => 'test_sendgrid'
        ]
    );

    return $result 
        ? '✅ Email enviado via SendGrid API!' 
        : '❌ Error al enviar';
});

/**
 * Test: Email con adjuntos via SendGrid
 */
Route::get('/test-sendgrid-attachments', function () {
    $emailService = new EmailService();
    
    return AttachmentHelper::withTemp(function ($temp) use ($emailService) {
        
        $attachments = [
            $temp->fromUrl(
                'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'documento-sendgrid.pdf'
            ),
            $temp->fromLocal(
                public_path('test.pdf'),
                'manual-local.pdf'
            )
        ];

        $result = $emailService->sendTemplateEmail(
            template: 'notificacion',
            variables: [
                'nombre' => 'Ulises',
                'titulo' => 'Test de adjuntos con SendGrid API',
                'mensaje' => 'Este email fue enviado usando SendGrid API con adjuntos.'
            ],
            options: [
                'to' => 'israel.jobs@pm.com',
                'cc' => ['cc@ejemplo.com'],
                'subject' => '📎 Test SendGrid con Adjuntos',
                'attachments' => $attachments
            ]
        );

        return $result 
            ? '✅ Email con adjuntos enviado via SendGrid!' 
            : '❌ Error';
    });
});
