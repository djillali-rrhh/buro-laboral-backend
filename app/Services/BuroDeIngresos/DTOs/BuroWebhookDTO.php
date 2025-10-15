<?php

namespace App\Services\BuroDeIngresos\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Representa el webhook recibido de Buro de Ingresos
 */
class BuroWebhookDTO
{
    public readonly string $event;
    public readonly string $verification_id;
    public readonly string $identifier;
    /** 
     * "in_progress" | "completed"
    */
    public readonly string $status;
    public readonly bool $data_available;
    public readonly bool $can_retry;

    /** "profile" | "employment" | "invoices" */
    public readonly array $entities;

    public readonly DateTimeImmutable $last_updated_at;
    public readonly DateTimeImmutable $timestamp;
    public readonly ?string $external_id;

    // Constantes para status
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    // Constantes para entities
    public const ENTITY_PROFILE = 'profile';
    public const ENTITY_EMPLOYMENT = 'employment';
    public const ENTITY_INVOICES = 'invoices';

    public function __construct(public readonly array $data)
    {
        $this->event = $data['event'];
        $this->verification_id = $data['verification_id'];
        $this->identifier = $data['identifier'];
        $this->data_available = (bool) $data['data_available'];
        $this->can_retry = (bool) $data['can_retry'];
        $this->external_id = $data['external_id'] ?? null;

        $this->status = $data['status'];
        $this->validateStatus($this->status);

        $this->entities = $data['entities'] ?? [];
        $this->validateEntities($this->entities);

        $this->last_updated_at = new DateTimeImmutable($data['last_updated_at']);
        $this->timestamp = new DateTimeImmutable($data['timestamp']);
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED], true)) {
            throw new InvalidArgumentException("Invalid verification status: {$status}");
        }
    }

    private function validateEntities(array $entities): void
    {
        $validEntities = [self::ENTITY_PROFILE, self::ENTITY_EMPLOYMENT, self::ENTITY_INVOICES];
        
        foreach ($entities as $entity) {
            if (!in_array($entity, $validEntities, true)) {
                throw new InvalidArgumentException("Invalid entity type: {$entity}");
            }
        }
    }

    public function hasEntity(string $entity): bool
    {
        return in_array($entity, $this->entities, true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}