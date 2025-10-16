<?php
namespace App\Services\EmailService\Strategies;

use App\Services\EmailService\Contracts\EmailStrategyInterface;
use App\Services\EmailService\Traits\NormalizesEmailData;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SmtpEmailStrategy implements EmailStrategyInterface
{
    use NormalizesEmailData;

    public function send(array $data): bool
    {
        try {
            // Normalizar y validar datos
            $data = $this->normalizeData($data);
            $this->validateData($data);

            // Si es un Mailable, enviarlo directamente
            if (!empty($data['mailable'])) {
                return $this->sendMailable($data);
            }

            // Enviar usando Mail::html
            Mail::html($data['html'] ?? $data['text'], function ($message) use ($data) {
                // Remitente
                $message->from($data['from'], $data['from_name'] ?? null);
                
                // Asunto
                $message->subject($data['subject']);
                
                // Destinatarios
                foreach ($data['to'] as $recipient) {
                    $message->to($recipient['email'], $recipient['name']);
                }
                
                // CC
                foreach ($data['cc'] as $cc) {
                    $message->cc($cc['email'], $cc['name']);
                }
                
                // BCC
                foreach ($data['bcc'] as $bcc) {
                    $message->bcc($bcc['email'], $bcc['name']);
                }
                
                // Reply To
                foreach ($data['reply_to'] as $replyTo) {
                    $message->replyTo($replyTo['email'], $replyTo['name']);
                }
                
                // Adjuntos
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
                
                // Prioridad
                if (!empty($data['priority'])) {
                    $message->priority($data['priority']);
                }
                
                // Headers personalizados
                if (!empty($data['headers'])) {
                    foreach ($data['headers'] as $key => $value) {
                        $message->getHeaders()->addTextHeader($key, $value);
                    }
                }
            });

            Log::info("[SMTP] Correo enviado exitosamente", [
                'to' => array_column($data['to'], 'email'),
                'subject' => $data['subject']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[SMTP] Error enviando correo", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Envía un Mailable de Laravel
     */
    protected function sendMailable(array $data): bool
    {
        $mailable = $this->resolveMailable($data['mailable']);
        
        // Aplicar configuraciones adicionales
        if (!empty($data['to'])) {
            $recipients = array_map(function($r) {
                return $r['name'] ? [$r['email'], $r['name']] : $r['email'];
            }, $data['to']);
            $mailable->to($recipients);
        }
        
        if (!empty($data['cc'])) {
            foreach ($data['cc'] as $cc) {
                $mailable->cc($cc['email'], $cc['name']);
            }
        }
        
        if (!empty($data['bcc'])) {
            foreach ($data['bcc'] as $bcc) {
                $mailable->bcc($bcc['email'], $bcc['name']);
            }
        }

        Mail::send($mailable);
        
        return true;
    }
}