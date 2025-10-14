<?php
// app/Services/BuroDeIngresos/DTOs/WebhookPayloadDTO.php

namespace App\Services\BuroDeIngresos\DTOs;

class WebhookPayloadDTO
{
    public readonly string $curp;
    public readonly string $status;
    public readonly bool $canRetry;
    public readonly array $rawPayload;

    public function __construct(array $payload)
    {
        $this->curp = $payload['identifier'] 
            ?? throw new \InvalidArgumentException('CURP no presente en payload');
            
        $this->status = $payload['status'] 
            ?? throw new \InvalidArgumentException('Status no presente en payload');
            
        $this->canRetry = $payload['can_retry'] ?? false;
        $this->rawPayload = $payload;
    }

    /**
     * Verifica si el webhook indica completado
     */
    public function esCompletado(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verifica si el webhook indica fallido
     */
    public function esFallido(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Verifica si el webhook indica en progreso
     */
    public function esEnProgreso(): bool
    {
        return $this->status === 'in_progress';
    }
}