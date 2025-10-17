<?php

namespace App\Services\EmailService;

use App\Jobs\SendEmailJob;
use App\Services\EmailService\Contracts\EmailStrategyInterface;
use Illuminate\Foundation\Bus\PendingDispatch;

/**
 * Servicio de correo electrónico con soporte para múltiples estrategias de envío.
 *
 * Este servicio permite enviar correos electrónicos de forma síncrona o asíncrona (cola)
 * utilizando diferentes proveedores (SMTP, SendGrid, etc.) mediante el patrón Strategy.
 */
class EmailService
{
    /**
     * Estrategia de envío de correo actualmente seleccionada.
     *
     * @var string Puede ser 'smtp', 'sendgrid', etc.
     */
    protected string $strategy;

    /**
     * Constructor del servicio de email.
     *
     * @param string $strategy Estrategia de envío ('smtp', 'sendgrid'). Default: 'smtp'
     */
    public function __construct(string $strategy = 'smtp')
    {
        $this->strategy = $strategy;
    }

    /**
     * Envía un email de forma síncrona (inmediata).
     *
     * Este método envía el correo de forma inmediata y espera a que se complete
     * antes de continuar con la ejecución. Para envíos asíncronos usa queue().
     *
     * @param array $data Datos del email con la siguiente estructura:
     * @param string $data['from'] Email del remitente (requerido)
     * @param string|null $data['from_name'] Nombre del remitente (opcional)
     * @param array|string $data['to'] Destinatarios (requerido). Puede ser:
     *        - string: 'user@example.com'
     *        - array simple: ['user1@example.com', 'user2@example.com']
     *        - array asociativo: ['user@example.com' => 'Usuario']
     * @param array|string|null $data['cc'] Copias (opcional, mismo formato que 'to')
     * @param array|string|null $data['bcc'] Copias ocultas (opcional, mismo formato que 'to')
     * @param array|string|null $data['reply_to'] Responder a (opcional, mismo formato que 'to')
     * @param string $data['subject'] Asunto del correo (requerido)
     * @param string|null $data['html'] Contenido HTML (opcional si se usa view o mailable)
     * @param string|null $data['text'] Contenido texto plano (opcional)
     * @param array|null $data['attachments'] Archivos adjuntos (opcional). Puede ser:
     *        - array simple: ['/path/to/file.pdf']
     *        - array de arrays: [['path' => '/path/file.pdf', 'name' => 'archivo.pdf', 'type' => 'application/pdf']]
     * @param \Illuminate\Mail\Mailable|null $data['mailable'] Instancia de Mailable (opcional)
     * @param string|null $data['view'] Vista Blade (opcional, ej: 'emails.welcome')
     * @param array|null $data['view_data'] Datos para la vista (opcional)
     * @param int|null $data['priority'] Prioridad del email 1-5 (1=máxima, 5=mínima)
     * @param array|null $data['headers'] Cabeceras personalizadas (opcional)
     *
     * @return bool True si el email se envió correctamente, false en caso contrario
     *
     * @throws \InvalidArgumentException Si faltan datos requeridos
     */
    public function send(array $data): bool
    {
        $strategyInstance = $this->resolveStrategy();
        return $strategyInstance->send($data);
    }

    /**
     * Envía un email a través de cola (asíncrono).
     *
     * Este método encola el correo para ser procesado de forma asíncrona por un worker.
     * Es la forma recomendada para enviar emails ya que no bloquea la ejecución
     * y permite reintentos automáticos en caso de fallo.
     *
     * @param array $data Datos del email (ver documentación de send() para estructura completa)
     * @param string $data['from'] Email del remitente (requerido)
     * @param string|null $data['from_name'] Nombre del remitente (opcional)
     * @param array|string $data['to'] Destinatarios (requerido). Puede ser:
     *        - string: 'user@example.com'
     *        - array simple: ['user1@example.com', 'user2@example.com']
     *        - array asociativo: ['user@example.com' => 'Usuario']
     * @param array|string|null $data['cc'] Copias (opcional, mismo formato que 'to')
     * @param array|string|null $data['bcc'] Copias ocultas (opcional, mismo formato que 'to')
     * @param array|string|null $data['reply_to'] Responder a (opcional, mismo formato que 'to')
     * @param string $data['subject'] Asunto del correo (requerido)
     * @param string|null $data['html'] Contenido HTML (opcional si se usa view o mailable)
     * @param string|null $data['text'] Contenido texto plano (opcional)
     * @param array|null $data['attachments'] Archivos adjuntos (opcional). Puede ser:
     *        - array simple: ['/path/to/file.pdf']
     *        - array de arrays: [['path' => '/path/file.pdf', 'name' => 'archivo.pdf', 'type' => 'application/pdf']]
     * @param \Illuminate\Mail\Mailable|null $data['mailable'] Instancia de Mailable (opcional)
     * @param string|null $data['view'] Vista Blade (opcional, ej: 'emails.welcome')
     * @param array|null $data['view_data'] Datos para la vista (opcional)
     * @param int|null $data['priority'] Prioridad del email 1-5 (1=máxima, 5=mínima)
     * @param array|null $data['headers'] Cabeceras personalizadas (opcional)
     * @param string|null $queue Nombre de la cola (opcional). Ejemplos:
     *        - null: Cola por defecto
     *        - 'emails': Cola genérica de emails
     *        - 'emails-high': Cola de alta prioridad
     *        - 'emails-low': Cola de baja prioridad
     * @param int|null $delay Segundos de delay antes de procesar (opcional)
     *
     * @return PendingDispatch Instancia de PendingDispatch para configuración adicional
     *
     * @throws \InvalidArgumentException Si faltan datos requeridos
     */
    public function queue(array $data, ?string $queue = null, ?int $delay = null): PendingDispatch
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
     * Envía un email a través de cola con delay específico.
     *
     * Método de conveniencia para enviar emails con delay. Es equivalente a
     * llamar queue() con el parámetro delay.
     *
     * @param array $data Datos del email (ver documentación de send())
     * @param int $seconds Segundos de delay antes de procesar
     * @param string|null $queue Nombre de la cola (opcional)
     *
     * @return PendingDispatch Instancia de PendingDispatch
     */
    public function later(array $data, int $seconds, ?string $queue = null): PendingDispatch
    {
        return $this->queue($data, $queue, $seconds);
    }

    /**
     * Envía múltiples emails en cola de forma eficiente.
     *
     * Este método permite encolar múltiples emails de una vez. Cada email
     * se procesa independientemente, por lo que el fallo de uno no afecta a los demás.
     *
     * @param array $emails Array de arrays, donde cada elemento contiene los datos de un email
     * @param string|null $queue Nombre de la cola (opcional, se aplica a todos los emails)
     *
     * @return void
     */
    public function queueBulk(array $emails, ?string $queue = null): void
    {
        foreach ($emails as $emailData) {
            $this->queue($emailData, $queue);
        }
    }

    /**
     * Cambia la estrategia de envío en tiempo de ejecución.
     *
     * Permite cambiar dinámicamente la estrategia de envío sin necesidad de
     * crear una nueva instancia del servicio.
     *
     * @param string $strategy Nueva estrategia ('smtp', 'sendgrid', etc.)
     *
     * @return $this Instancia actual para encadenamiento fluido
     */
    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * Resuelve y crea una instancia de la estrategia de envío.
     *
     * Este método interno crea la instancia apropiada de la estrategia
     * basándose en el string de estrategia configurado.
     *
     * @return EmailStrategyInterface Instancia de la estrategia de email
     *
     * @internal Este método es de uso interno y no debería llamarse directamente
     */
    protected function resolveStrategy(): EmailStrategyInterface
    {
        return match ($this->strategy) {
            'sendgrid' => new \App\Services\EmailService\Strategies\SendGridEmailStrategy(),
            'smtp' => new \App\Services\EmailService\Strategies\SmtpEmailStrategy(),
            default => new \App\Services\EmailService\Strategies\SmtpEmailStrategy(),
        };
    }
}
