<?php

namespace App\Services\EmailService;

use App\Mail\DynamicEmail;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    protected $sendGridService;

    public function __construct()
    {
        $this->sendGridService = new SendGridService();
    }

    /**
     * Enviar email con plantilla dinámica
     */
    public function sendTemplateEmail(string $template, array $variables, array $options): bool
    {
        $from = $options['from'] ?? config('mail.from.address');
        $fromName = config('mail.from.name');
        $to = $options['to'];
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $subject = $options['subject'];
        $attachments = $options['attachments'] ?? [];
        $triggeredBy = $options['triggered_by'] ?? 'system';

        // Validar y limpiar destinatarios
        $cleanedRecipients = $this->cleanRecipients($to, $cc, $bcc);

        // Renderizar la vista Blade a HTML
        $htmlContent = view("emails.{$template}", $variables)->render();

        // Decidir si usar SendGrid API o Laravel Mail
        if ($this->sendGridService->isEnabled()) {
            // Usar SendGrid API
            $result = $this->sendGridService->send(
                from: $from,
                fromName: $fromName,
                to: $cleanedRecipients['to'],
                subject: $subject,
                htmlContent: $htmlContent,
                cc: $cleanedRecipients['cc'],
                bcc: $cleanedRecipients['bcc'],
                attachments: $attachments
            );

            return $result['success'];
        } else {
            // Usar Laravel Mail (Mailtrap u otro SMTP)
            $mailable = new DynamicEmail(
                view: "emails.{$template}",
                data: $variables,
                subject: $subject,
                attachments: $attachments
            );

            $mailable->from($from, $fromName);

            $mailInstance = Mail::to($cleanedRecipients['to']);

            if (!empty($cleanedRecipients['cc'])) {
                $mailInstance->cc($cleanedRecipients['cc']);
            }

            if (!empty($cleanedRecipients['bcc'])) {
                $mailInstance->bcc($cleanedRecipients['bcc']);
            }

            $mailInstance->send($mailable);

            return true;
        }
    }

    /**
     * Enviar email simple sin plantilla
     */
    public function sendBasicEmail(string $body, array $options): bool
    {
        $from = $options['from'] ?? config('mail.from.address');
        $fromName = config('mail.from.name');
        $to = $options['to'];
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $subject = $options['subject'];
        $triggeredBy = $options['triggered_by'] ?? 'system';

        $cleanedRecipients = $this->cleanRecipients($to, $cc, $bcc);

        if ($this->sendGridService->isEnabled()) {
            // Usar SendGrid API
            $result = $this->sendGridService->send(
                from: $from,
                fromName: $fromName,
                to: $cleanedRecipients['to'],
                subject: $subject,
                htmlContent: $body,
                cc: $cleanedRecipients['cc'],
                bcc: $cleanedRecipients['bcc'],
                attachments: []
            );

            return $result['success'];
        } else {
            // Usar Laravel Mail
            Mail::html($body, function ($message) use ($from, $fromName, $cleanedRecipients, $subject) {
                $message->from($from, $fromName)
                    ->to($cleanedRecipients['to'])
                    ->subject($subject);

                if (!empty($cleanedRecipients['cc'])) {
                    $message->cc($cleanedRecipients['cc']);
                }

                if (!empty($cleanedRecipients['bcc'])) {
                    $message->bcc($cleanedRecipients['bcc']);
                }
            });

            return true;
        }
    }

    /**
     * Limpiar y validar destinatarios
     */
    private function cleanRecipients($to, $cc, $bcc): array
    {
        $toList = is_array($to) ? $to : [$to];
        $ccList = is_array($cc) ? $cc : ($cc ? [$cc] : []);
        $bccList = is_array($bcc) ? $bcc : ($bcc ? [$bcc] : []);

        $toList = array_filter($toList, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $ccList = array_filter($ccList, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        $bccList = array_filter($bccList, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

        $toList = array_unique($toList);
        $ccList = array_unique($ccList);
        $bccList = array_unique($bccList);

        $toLower = array_map('strtolower', $toList);
        $ccList = array_filter($ccList, fn($email) => !in_array(strtolower($email), $toLower));

        $ccLower = array_map('strtolower', $ccList);
        $bccList = array_filter(
            $bccList,
            fn($email) =>
            !in_array(strtolower($email), $toLower) &&
                !in_array(strtolower($email), $ccLower)
        );

        return [
            'to' => array_values($toList),
            'cc' => array_values($ccList),
            'bcc' => array_values($bccList)
        ];
    }
}