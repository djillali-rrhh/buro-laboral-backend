<?php
namespace App\Services\EmailService\Contracts;

interface EmailStrategyInterface
{
    /**
     * Envía un correo electrónico
     * 
     * @param array $data Datos del correo con la siguiente estructura:
     *   - from: string (email del remitente)
     *   - from_name: string|null (nombre del remitente)
     *   - to: array|string (destinatarios)
     *   - cc: array|string|null (copias)
     *   - bcc: array|string|null (copias ocultas)
     *   - reply_to: array|string|null (responder a)
     *   - subject: string (asunto)
     *   - html: string|null (contenido HTML)
     *   - text: string|null (contenido texto plano)
     *   - attachments: array|null (archivos adjuntos)
     *   - mailable: string|null (clase Mailable de Laravel)
     *   - view: string|null (vista de Laravel)
     *   - view_data: array|null (datos para la vista)
     *   - priority: int|null (1=highest, 5=lowest)
     *   - headers: array|null (cabeceras personalizadas)
     * 
     * @return bool
     */
    public function send(array $data): bool;
}