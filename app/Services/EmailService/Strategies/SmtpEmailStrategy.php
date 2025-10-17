<?php

namespace App\Services\EmailService\Strategies;

use App\Services\EmailService\Contracts\EmailStrategyInterface;
use App\Services\EmailService\Traits\NormalizesEmailData;
use App\Services\EmailService\Traits\HasEmailLogging;
use App\Services\EmailService\Traits\LogsEmails;
use Illuminate\Support\Facades\Mail;

/**
 * Estrategia para enviar correos electrónicos usando SMTP.
 *
 * @package App\Services\EmailService\Strategies
 */
class SmtpEmailStrategy implements EmailStrategyInterface
{
    use NormalizesEmailData, HasEmailLogging, LogsEmails;

    public function send(array $data): bool
    {
        $startTime = microtime(true);
        $emailLog = null;

        try {
            // Normalizar y validar datos
            $data = $this->normalizeData($data);
            $this->validateData($data);

            // Crear registro de log
            $emailLog = $this->createEmailLog($data, 'smtp');

            // Log inicio
            $this->logSendingEmail($data);

            // Si es un Mailable, enviarlo directamente
            if (!empty($data['mailable'])) {
                $result = $this->sendMailable($data);
                
                if ($result) {
                    // Log de éxito
                    if ($emailLog) {
                        $this->logEmailSuccess($emailLog, $startTime);
                    }
                    $this->logEmailSent($data);
                    return true;
                }
                
                // Log de fallo
                if ($emailLog) {
                    $this->logEmailFailure($emailLog, 'Error al enviar Mailable');
                }
                return false;
            }

            // Enviar usando Mail::html
            Mail::html($data['html'] ?? $data['text'], function ($message) use ($data) {
                $message->from($data['from'], $data['from_name'] ?? null);
                $message->subject($data['subject']);
                
                foreach ($data['to'] as $recipient) {
                    $message->to($recipient['email'], $recipient['name']);
                }
                
                foreach ($data['cc'] as $cc) {
                    $message->cc($cc['email'], $cc['name']);
                }
                
                foreach ($data['bcc'] as $bcc) {
                    $message->bcc($bcc['email'], $bcc['name']);
                }
                
                foreach ($data['reply_to'] as $replyTo) {
                    $message->replyTo($replyTo['email'], $replyTo['name']);
                }
                
                foreach ($data['attachments'] as $file) {
                    if ($file['disposition'] === 'inline') {
                        $message->attachData(
                            file_get_contents($file['path']),
                            $file['name'],
                            ['mime' => $file['type']]
                        );
                    } else {
                        $message->attach($file['path'], [
                            'as' => $file['name'],
                            'mime' => $file['type']
                        ]);
                    }
                }
                
                if (!empty($data['priority'])) {
                    $message->priority($data['priority']);
                }
                
                if (!empty($data['headers'])) {
                    foreach ($data['headers'] as $key => $value) {
                        $message->getHeaders()->addTextHeader($key, $value);
                    }
                }
            });

            // Log de éxito
            if ($emailLog) {
                $this->logEmailSuccess($emailLog, $startTime);
            }
            $this->logEmailSent($data);

            return true;
        } catch (\Exception $e) {
            // Log de excepción
            if ($emailLog) {
                $this->logEmailFailure(
                    $emailLog,
                    $e->getMessage(),
                    null,
                    [
                        'exception_class' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]
                );
            }

            $this->logException($e, $data);
            return false;
        }
    }

    /**
     * Envía un Mailable de Laravel.
     *
     * @param array $data Datos del email con mailable incluido
     * @return bool
     */
    protected function sendMailable(array $data): bool
    {
        try {
            $mailable = $this->resolveMailable($data['mailable']);
            
            // Aplicar destinatarios si se proporcionaron
            if (!empty($data['to'])) {
                foreach ($data['to'] as $recipient) {
                    if (!empty($recipient['name'])) {
                        $mailable->to($recipient['email'], $recipient['name']);
                    } else {
                        $mailable->to($recipient['email']);
                    }
                }
            }
            
            // Aplicar CC
            if (!empty($data['cc'])) {
                foreach ($data['cc'] as $cc) {
                    if (!empty($cc['name'])) {
                        $mailable->cc($cc['email'], $cc['name']);
                    } else {
                        $mailable->cc($cc['email']);
                    }
                }
            }
            
            // Aplicar BCC
            if (!empty($data['bcc'])) {
                foreach ($data['bcc'] as $bcc) {
                    if (!empty($bcc['name'])) {
                        $mailable->bcc($bcc['email'], $bcc['name']);
                    } else {
                        $mailable->bcc($bcc['email']);
                    }
                }
            }

            // Aplicar Reply-To
            if (!empty($data['reply_to'])) {
                foreach ($data['reply_to'] as $replyTo) {
                    if (!empty($replyTo['name'])) {
                        $mailable->replyTo($replyTo['email'], $replyTo['name']);
                    } else {
                        $mailable->replyTo($replyTo['email']);
                    }
                }
            }

            // Enviar
            Mail::send($mailable);
            
            $this->logEmailSent($data);
            
            return true;
        } catch (\Exception $e) {
            $this->logException($e, $data);
            return false;
        }
    }
}