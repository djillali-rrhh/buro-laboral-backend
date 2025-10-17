<?php
namespace App\Services\EmailService\Traits;

use Illuminate\Support\Facades\View;
use Illuminate\Mail\Mailable;

trait NormalizesEmailData
{
    /**
     * Normaliza los datos del correo
     */
    protected function normalizeData(array $data): array
    {
        // Normalizar destinatarios a arrays
        $data['to'] = $this->normalizeRecipients($data['to'] ?? []);
        $data['cc'] = $this->normalizeRecipients($data['cc'] ?? []);
        $data['bcc'] = $this->normalizeRecipients($data['bcc'] ?? []);
        $data['reply_to'] = $this->normalizeRecipients($data['reply_to'] ?? []);
        
        // Normalizar adjuntos
        $data['attachments'] = $this->normalizeAttachments($data['attachments'] ?? []);
        
        // Procesar contenido
        $data = $this->processContent($data);
        
        return $data;
    }

    /**
     * Normaliza destinatarios a formato array
     */
    protected function normalizeRecipients($recipients): array
    {
        if (empty($recipients)) {
            return [];
        }
        
        if (is_string($recipients)) {
            // 'user@example.com' -> [['email' => 'user@example.com', 'name' => null]]
            return [['email' => $recipients, 'name' => null]];
        }
        
        $normalized = [];
        foreach ((array) $recipients as $key => $value) {
            if (is_array($value)) {
                // ['email' => 'test@test.com', 'name' => 'Test']
                $normalized[] = [
                    'email' => $value['email'] ?? $value[0] ?? $key,
                    'name' => $value['name'] ?? $value[1] ?? null
                ];
            } else {
                // 'test@test.com' o 'test@test.com' => 'Test Name'
                $normalized[] = [
                    'email' => is_numeric($key) ? $value : $key,
                    'name' => is_numeric($key) ? null : $value
                ];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Normaliza adjuntos
     */
    protected function normalizeAttachments($attachments): array
    {
        if (empty($attachments)) {
            return [];
        }
        
        $normalized = [];
        foreach ($attachments as $attachment) {
            if (is_string($attachment)) {
                $normalized[] = [
                    'path' => $attachment,
                    'name' => basename($attachment),
                    'type' => mime_content_type($attachment),
                    'disposition' => 'attachment'
                ];
            } else {
                $normalized[] = [
                    'path' => $attachment['path'],
                    'name' => $attachment['name'] ?? basename($attachment['path']),
                    'type' => $attachment['type'] ?? mime_content_type($attachment['path']),
                    'disposition' => $attachment['disposition'] ?? 'attachment'
                ];
            }
        }
        
        return $normalized;
    }

    /**
     * Procesa el contenido del correo (mailable, view, html)
     */
    protected function processContent(array $data): array
    {
        // Si se proporciona un Mailable
        if (!empty($data['mailable'])) {
            $mailable = $this->resolveMailable($data['mailable']);
            $data['html'] = $mailable->render();
            $data['subject'] = $data['subject'] ?? $mailable->subject;
            return $data;
        }
        
        // Si se proporciona una vista de Laravel
        if (!empty($data['view'])) {
            $data['html'] = View::make($data['view'], $data['view_data'] ?? [])->render();
            return $data;
        }
        
        return $data;
    }

    /**
     * Resuelve un Mailable desde string o instancia
     */
    protected function resolveMailable($mailable): Mailable
    {
        if ($mailable instanceof Mailable) {
            return $mailable;
        }
        
        if (is_string($mailable) && class_exists($mailable)) {
            return app($mailable);
        }
        
        throw new \InvalidArgumentException("Invalid mailable provided");
    }

    /**
     * Valida que los datos requeridos estén presentes
     */
    protected function validateData(array $data): void
    {
        if (empty($data['to'])) {
            throw new \InvalidArgumentException("El campo 'to' es requerido");
        }
        
        if (empty($data['from'])) {
            throw new \InvalidArgumentException("El campo 'from' es requerido");
        }
        
        if (empty($data['subject'])) {
            throw new \InvalidArgumentException("El campo 'subject' es requerido");
        }
        
        if (empty($data['html']) && empty($data['text']) && empty($data['view']) && empty($data['mailable'])) {
            throw new \InvalidArgumentException("Debe proporcionar contenido: html, text, view o mailable");
        }
    }
}