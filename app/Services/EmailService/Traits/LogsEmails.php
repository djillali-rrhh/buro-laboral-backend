<?php

namespace App\Services\EmailService\Traits;

use App\Models\EmailLog;

/**
 * Trait para registrar envíos de email en la base de datos.
 *
 * @package App\Services\EmailService\Traits
 */
trait LogsEmails
{
    /**
     * Crea un registro de log antes de enviar el email.
     *
     * @param array $data Datos del email
     * @param string $strategy Estrategia utilizada
     * @return EmailLog
     */
    protected function createEmailLog(array $data, string $strategy): EmailLog
    {
        return EmailLog::create([
            'from_email' => $data['from'],
            'from_name' => $data['from_name'] ?? null,
            'to_recipients' => $data['to'] ?? [],
            'cc_recipients' => $data['cc'] ?? [],
            'bcc_recipients' => $data['bcc'] ?? [],
            'subject' => $data['subject'] ?? 'Sin asunto',
            'strategy' => $strategy,
            'template_type' => $this->getTemplateType($data),
            'template_name' => $this->getTemplateName($data),
            'status' => 'pending',
            'attempts' => 1,
            'has_attachments' => !empty($data['attachments']),
            'attachments_count' => count($data['attachments'] ?? []),
            'content_size_bytes' => strlen($data['html'] ?? $data['text'] ?? ''),
        ]);
    }

    /**
     * Actualiza el log como enviado exitosamente.
     *
     * @param EmailLog $log
     * @param float $startTime
     * @param int|null $statusCode
     * @param array|null $responseData
     * @return void
     */
    protected function logEmailSuccess(EmailLog $log, float $startTime, ?int $statusCode = null, ?array $responseData = null): void
    {
        $duration = (microtime(true) - $startTime) * 1000; // en milisegundos

        $log->update([
            'status' => 'sent',
            'sent_at' => now(),
            'send_duration_ms' => $duration,
            'http_status_code' => $statusCode,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Actualiza el log como fallido.
     *
     * @param EmailLog $log
     * @param string $errorMessage
     * @param int|null $statusCode
     * @param array|null $responseData
     * @return void
     */
    protected function logEmailFailure(EmailLog $log, string $errorMessage, ?int $statusCode = null, ?array $responseData = null): void
    {
        $log->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'http_status_code' => $statusCode,
            'response_data' => $responseData,
        ]);
    }

    /**
     * Detecta el tipo de plantilla utilizada.
     *
     * @param array $data
     * @return string|null
     */
    protected function getTemplateType(array $data): ?string
    {
        if (!empty($data['mailable'])) {
            return 'mailable';
        }
        if (!empty($data['view'])) {
            return 'view';
        }
        if (!empty($data['html'])) {
            return 'html';
        }
        if (!empty($data['text'])) {
            return 'text';
        }
        return null;
    }

    /**
     * Obtiene el nombre de la plantilla.
     *
     * @param array $data
     * @return string|null
     */
    protected function getTemplateName(array $data): ?string
    {
        if (!empty($data['mailable'])) {
            return is_string($data['mailable']) 
                ? $data['mailable'] 
                : get_class($data['mailable']);
        }
        if (!empty($data['view'])) {
            return $data['view'];
        }
        return null;
    }
}