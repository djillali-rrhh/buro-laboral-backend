<?php

namespace App\Services\EmailService;

use SendGrid\Mail\Mail;
use Illuminate\Support\Facades\Log;

class SendGridService
{
    protected $sendgrid;
    protected $enabled;

    public function __construct()
    {
        $apiKey = config('services.sendgrid.api_key');
        $this->enabled = config('services.sendgrid.enabled', false);
        
        if ($this->enabled && $apiKey) {
            $this->sendgrid = new \SendGrid($apiKey);
        }
    }

    /**
     * Verificar si SendGrid está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->sendgrid !== null;
    }

    /**
     * Enviar email usando SendGrid API
     */
    public function send(
        string $from,
        string $fromName,
        array $to,
        string $subject,
        string $htmlContent,
        array $cc = [],
        array $bcc = [],
        array $attachments = []
    ): array {
        if (!$this->isEnabled()) {
            throw new \Exception('SendGrid no está habilitado. Verifica SENDGRID_ENABLED y SENDGRID_API_KEY en .env');
        }

        try {
            $mail = new Mail();
            
            // From
            $mail->setFrom($from, $fromName);
            
            // Subject
            $mail->setSubject($subject);
            
            // Content
            $mail->addContent("text/html", $htmlContent);
            
            // To (destinatarios principales)
            foreach ($to as $email) {
                $mail->addTo($email);
            }
            
            // CC
            foreach ($cc as $email) {
                $mail->addCc($email);
            }
            
            // BCC
            foreach ($bcc as $email) {
                $mail->addBcc($email);
            }
            
            // Attachments
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $fileContent = base64_encode(file_get_contents($attachment['path']));
                    $fileName = $attachment['name'] ?? basename($attachment['path']);
                    
                    // ✨ Limpiar MIME type (remover ; y saltos de línea)
                    $mimeType = $attachment['mime'] ?? 'application/octet-stream';
                    $mimeType = $this->cleanMimeType($mimeType);
                    
                    $mail->addAttachment(
                        $fileContent,
                        $mimeType,
                        $fileName,
                        "attachment"
                    );
                }
            }




            // Enviar
            $response = $this->sendgrid->send($mail);
            $statusCode = $response->statusCode();
            
            if ($statusCode !== 202) {
                $responseBody = $response->body();
                $json = json_decode($responseBody, true);
                $errorMessage = $json['errors'][0]['message'] ?? 'Error desconocido';
                
                Log::error('[SendGrid] Error enviando email', [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'to' => $to,
                    'subject' => $subject
                ]);
                
                return [
                    'success' => false,
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'message_id' => null
                ];
            }
            
            // Extraer Message ID
            $messageId = null;
            foreach ($response->headers() as $header) {
                if (stripos($header, 'X-Message-Id:') === 0) {
                    $messageId = trim(substr($header, strlen('X-Message-Id:')));
                    break;
                }
            }
            
            Log::info('[SendGrid] Email enviado exitosamente', [
                'to' => $to,
                'subject' => $subject,
                'message_id' => $messageId,
                'status' => $statusCode
            ]);
            
            return [
                'success' => true,
                'status' => $statusCode,
                'message_id' => $messageId,
                'error' => null
            ];
            
        } catch (\Exception $e) {
            Log::error('[SendGrid] Excepción al enviar email', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject
            ]);
            
            return [
                'success' => false,
                'status' => 'exception',
                'error' => $e->getMessage(),
                'message_id' => null
            ];
        }
    }

    /**
     * Limpiar MIME type para SendGrid
     * Remueve caracteres no permitidos: ; y CRLF
     */
    private function cleanMimeType(string $mimeType): string
    {
        // Remover todo después del primer ";"
        if (strpos($mimeType, ';') !== false) {
            $mimeType = substr($mimeType, 0, strpos($mimeType, ';'));
        }
        
        // Remover saltos de línea y retornos de carro
        $mimeType = str_replace(["\r", "\n", "\r\n"], '', $mimeType);
        
        // Trim espacios
        $mimeType = trim($mimeType);
        
        // Si quedó vacío, usar default
        if (empty($mimeType)) {
            $mimeType = 'application/octet-stream';
        }
        
        return $mimeType;
    }
}