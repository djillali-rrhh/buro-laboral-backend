<?php
namespace App\Services\EmailService\Strategies;

use App\Services\EmailService\Contracts\EmailStrategyInterface;
use App\Services\EmailService\Traits\NormalizesEmailData;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Attachment;
use Illuminate\Support\Facades\Log;

class SendGridEmailStrategy implements EmailStrategyInterface
{
    use NormalizesEmailData;

    protected SendGrid $client;

    public function __construct()
    {
        $this->client = new SendGrid(config('services.sendgrid.key'));
    }

    public function send(array $data): bool
    {
        try {
            // Normalizar y validar datos
            $data = $this->normalizeData($data);
            $this->validateData($data);

            $email = new Mail();
            
            // Remitente
            $email->setFrom($data['from'], $data['from_name'] ?? null);
            
            // Asunto
            $email->setSubject($data['subject']);
            
            // Destinatarios
            foreach ($data['to'] as $recipient) {
                $email->addTo($recipient['email'], $recipient['name']);
            }

            // CC
            foreach ($data['cc'] as $cc) {
                $email->addCc($cc['email'], $cc['name']);
            }

            // BCC
            foreach ($data['bcc'] as $bcc) {
                $email->addBcc($bcc['email'], $bcc['name']);
            }

            // Reply To
            foreach ($data['reply_to'] as $replyTo) {
                $email->setReplyTo($replyTo['email'], $replyTo['name']);
            }

            // Contenido HTML
            if (!empty($data['html'])) {
                $email->addContent("text/html", $data['html']);
            }

            // Contenido texto plano
            if (!empty($data['text'])) {
                $email->addContent("text/plain", $data['text']);
            }

            // Adjuntos
            foreach ($data['attachments'] as $file) {
                $attachment = new Attachment();
                $attachment->setContent(base64_encode(file_get_contents($file['path'])));
                $attachment->setType($file['type']);
                $attachment->setFilename($file['name']);
                $attachment->setDisposition($file['disposition']);
                $email->addAttachment($attachment);
            }

            // Prioridad
            if (!empty($data['priority'])) {
                $email->addHeader('X-Priority', (string) $data['priority']);
            }

            // Headers personalizados
            if (!empty($data['headers'])) {
                foreach ($data['headers'] as $key => $value) {
                    $email->addHeader($key, $value);
                }
            }

            // Enviar
            $response = $this->client->send($email);

            if ($response->statusCode() >= 400) {
                Log::error("[SendGrid] Error enviando correo", [
                    'status' => $response->statusCode(),
                    'body' => $response->body(),
                    'to' => array_column($data['to'], 'email')
                ]);
                return false;
            }

            Log::info("[SendGrid] Correo enviado exitosamente", [
                'to' => array_column($data['to'], 'email'),
                'subject' => $data['subject']
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[SendGrid] Exception al enviar correo", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}