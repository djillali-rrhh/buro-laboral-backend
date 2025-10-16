<?php

namespace App\Services\EmailService;

use App\Jobs\SendEmailJob;

/**
 * Servicio de correo electrónico que utiliza una estrategia para enviar correos.
 */
class EmailService
{
    protected string $strategy;

    public function __construct(string $strategy = 'smtp')
    {
        $this->strategy = $strategy;
    }

    /**
     * Envía un email de forma síncrona
     * @param array $data Datos del email
     *  - from: string (email del remitente)
     *  - from_name: string|null (nombre del remitente)
     *  - to: array|string (destinatarios)
     *  - cc: array|string|null (copias)
     *  - bcc: array|string|null (copias ocultas)
     *  - reply_to: array|string|null (responder a)
     *  - subject: string (asunto)
     *  - html: string|null (contenido HTML)
     *  - text: string|null (contenido texto plano)
     *  - attachments: array|null (archivos adjuntos)
     *  - mailable: string|null (clase Mailable de Laravel)
     *  - view: string|null (vista de Laravel)
     *  - view_data: array|null (datos para la vista)
     *  - priority: int|null (1=highest, 5=lowest)
     *  - headers: array|null (cabeceras personalizadas)
     * @return bool
     */
    public function send(array $data): bool
    {
        $strategyInstance = $this->resolveStrategy();
        return $strategyInstance->send($data);
    }

    /**
     * Envía un email a través de cola
     * @param array $data Datos del email
     * @param string|null $queue Nombre de la cola (opcional)
     * @param int|null $delay Segundos de delay (opcional)
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue(array $data, ?string $queue = null, ?int $delay = null)
    {
        if ($queue) {
            $data['queue'] = $queue;
        }

        if ($delay) {
            $data['delay'] = $delay;
        }

        return SendEmailJob::dispatch($data, $this->strategy);
    }

    /**
     * Envía un email a través de cola con delay
     * @param array $data Datos del email
     * @param int $seconds Segundos de delay
     * @param string|null $queue Nombre de la cola (opcional)
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function later(array $data, int $seconds, ?string $queue = null)
    {
        return $this->queue($data, $queue, $seconds);
    }

    /**
     * Envía múltiples emails en cola
     * @param array $emails Array de datos de emails
     * @param string|null $queue Nombre de la cola (opcional)
     * @return void
     */
    public function queueBulk(array $emails, ?string $queue = null): void
    {
        foreach ($emails as $emailData) {
            $this->queue($emailData, $queue);
        }
    }

    /**
     * Cambiar la estrategia en tiempo de ejecución
     * @param string $strategy Nueva estrategia
     * @return $this
     */
    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * Resolver la estrategia desde el string
     * @return EmailStrategyInterface
     */
    protected function resolveStrategy()
    {
        return match ($this->strategy) {
            'sendgrid' => new \App\Services\EmailService\Strategies\SendGridEmailStrategy(),
            'smtp' => new \App\Services\EmailService\Strategies\SmtpEmailStrategy(),
            default => new \App\Services\EmailService\Strategies\SmtpEmailStrategy(),
        };
    }
}
