<?php

namespace App\Services\EmailService\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait para agregar capacidades de logging a las estrategias de email.
 *
 * @package App\Services\EmailService\Traits
 */
trait HasEmailLogging
{
    /**
     * Registra un evento de información.
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $strategy = class_basename($this);
        Log::channel('emails')->info("[{$strategy}] {$message}", $context);
    }

    /**
     * Registra un evento de error.
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $strategy = class_basename($this);
        Log::channel('emails')->error("[{$strategy}] {$message}", $context);
    }

    /**
     * Registra un evento de advertencia.
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $strategy = class_basename($this);
        Log::channel('emails')->warning("[{$strategy}] {$message}", $context);
    }

    /**
     * Registra un evento de depuración.
     *
     * @param string $message Mensaje a registrar
     * @param array $context Contexto adicional
     * @return void
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $strategy = class_basename($this);
        Log::channel('emails')->debug("[{$strategy}] {$message}", $context);
    }

    /**
     * Registra el inicio del envío de un email.
     *
     * @param array $data Datos del email
     * @return void
     */
    protected function logSendingEmail(array $data): void
    {
        $this->logInfo('Enviando correo', [
            'to' => is_array($data['to']) ? array_column($data['to'], 'email') : $data['to'],
            'subject' => $data['subject'] ?? 'no subject',
            'has_attachments' => !empty($data['attachments']),
            'attachments_count' => count($data['attachments'] ?? [])
        ]);
    }

    /**
     * Registra el éxito del envío de un email.
     *
     * @param array $data Datos del email
     * @param int|null $statusCode Código de estado HTTP (opcional)
     * @return void
     */
    protected function logEmailSent(array $data, ?int $statusCode = null): void
    {
        $context = [
            'to' => is_array($data['to']) ? array_column($data['to'], 'email') : $data['to'],
            'subject' => $data['subject'] ?? 'no subject'
        ];

        if ($statusCode !== null) {
            $context['status_code'] = $statusCode;
        }

        $this->logInfo('Correo enviado exitosamente', $context);
    }

    /**
     * Registra el fallo del envío de un email.
     *
     * @param array $data Datos del email
     * @param string $reason Razón del fallo
     * @param int|null $statusCode Código de estado HTTP (opcional)
     * @return void
     */
    protected function logEmailFailed(array $data, string $reason, ?int $statusCode = null): void
    {
        $context = [
            'to' => is_array($data['to']) ? array_column($data['to'], 'email') : $data['to'],
            'subject' => $data['subject'] ?? 'no subject',
            'reason' => $reason
        ];

        if ($statusCode !== null) {
            $context['status_code'] = $statusCode;
        }

        $this->logError('Error enviando correo', $context);
    }

    /**
     * Registra una excepción durante el envío.
     *
     * @param \Throwable $exception Excepción capturada
     * @param array $data Datos del email
     * @return void
     */
    protected function logException(\Throwable $exception, array $data = []): void
    {
        $this->logError('Exception al enviar correo', [
            'to' => isset($data['to']) 
                ? (is_array($data['to']) ? array_column($data['to'], 'email') : $data['to'])
                : 'unknown',
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
    
     /**
     * Registra métricas de rendimiento.
     *
     * @param float $startTime Timestamp de inicio
     * @param array $data Datos del email
     * @return void
     */
    protected function logPerformance(float $startTime, array $data): void
    {
        $duration = microtime(true) - $startTime;
        
        $this->logDebug('Métricas de envío', [
            'to' => is_array($data['to']) ? array_column($data['to'], 'email') : $data['to'],
            'duration_seconds' => round($duration, 3),
            'attachments_count' => count($data['attachments'] ?? []),
            'content_size_kb' => round(strlen($data['html'] ?? $data['text'] ?? '') / 1024, 2)
        ]);
    }
}