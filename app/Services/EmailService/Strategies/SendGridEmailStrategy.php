<?php

namespace App\Services\EmailService\Strategies;

use App\Services\EmailService\Contracts\EmailStrategyInterface;
use App\Services\EmailService\Traits\NormalizesEmailData;
use App\Services\EmailService\Traits\HasEmailLogging;
use App\Services\EmailService\Traits\LogsEmails;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Attachment;
use SendGrid\Mail\MailSettings;
use SendGrid\Mail\SandBoxMode;

class SendGridEmailStrategy implements EmailStrategyInterface
{
    use NormalizesEmailData, HasEmailLogging, LogsEmails;

    protected SendGrid $client;
    protected bool $sandboxMode;

    public function __construct()
    {
        $this->client = new SendGrid(config('services.sendgrid.key'));
        $this->sandboxMode = config('services.sendgrid.sandbox', false);
    }

    public function send(array $data): bool
    {
        $startTime = microtime(true);
        $emailLog = null;

        try {
            // Normalizar y validar datos
            $data = $this->normalizeData($data);
            $this->validateData($data);

            // Crear registro de log
            $emailLog = $this->createEmailLog($data, 'sendgrid');

            // Log inicio
            $this->logSendingEmail($data);

            $email = new Mail();
            
            $email->setFrom($data['from'], $data['from_name'] ?? null);
            $email->setSubject($data['subject']);
            
            foreach ($data['to'] as $recipient) {
                $email->addTo($recipient['email'], $recipient['name']);
            }

            foreach ($data['cc'] as $cc) {
                $email->addCc($cc['email'], $cc['name']);
            }

            foreach ($data['bcc'] as $bcc) {
                $email->addBcc($bcc['email'], $bcc['name']);
            }

            foreach ($data['reply_to'] as $replyTo) {
                $email->setReplyTo($replyTo['email'], $replyTo['name']);
            }

            if (!empty($data['html'])) {
                $email->addContent("text/html", $data['html']);
            }

            if (!empty($data['text'])) {
                $email->addContent("text/plain", $data['text']);
            }

            foreach ($data['attachments'] as $file) {
                $attachment = new Attachment();
                $attachment->setContent(base64_encode(file_get_contents($file['path'])));
                $attachment->setType($file['type']);
                $attachment->setFilename($file['name']);
                $attachment->setDisposition($file['disposition']);
                $email->addAttachment($attachment);
            }

            if (!empty($data['priority'])) {
                $email->addHeader('X-Priority', (string) $data['priority']);
            }

            if (!empty($data['headers'])) {
                foreach ($data['headers'] as $key => $value) {
                    $email->addHeader($key, $value);
                }
            }

            // Modo SANDBOX
            if ($this->sandboxMode) {
                $mailSettings = new MailSettings();
                $sandboxMode = new SandBoxMode();
                $sandboxMode->setEnable(true);
                $mailSettings->setSandboxMode($sandboxMode);
                $email->setMailSettings($mailSettings);
                
                $this->logWarning('Modo SANDBOX activado - Email NO será enviado realmente', [
                    'to' => array_column($data['to'], 'email'),
                    'subject' => $data['subject']
                ]);
            }

            // Enviar
            $response = $this->client->send($email);

            if ($response->statusCode() >= 400) {
                // Log de fallo
                if ($emailLog) {
                    $this->logEmailFailure(
                        $emailLog,
                        $response->body(),
                        $response->statusCode(),
                        ['body' => $response->body()]
                    );
                }

                $this->logEmailFailed(
                    $data, 
                    $response->body(), 
                    $response->statusCode()
                );
                return false;
            }

            // Log de éxito
            if ($emailLog) {
                $this->logEmailSuccess(
                    $emailLog,
                    $startTime,
                    $response->statusCode(),
                    ['message_id' => $response->headers()['X-Message-Id'] ?? null]
                );
            }

            $this->logEmailSent($data, $response->statusCode());

            return true;
        } catch (\Exception $e) {
            // Log de excepción
            if ($emailLog) {
                $this->logEmailFailure(
                    $emailLog,
                    $e->getMessage(),
                    null,
                    ['trace' => $e->getTraceAsString()]
                );
            }

            $this->logException($e, $data);
            return false;
        }
    }
}