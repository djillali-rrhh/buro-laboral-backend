<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\RegistrosPalenca;
use App\Services\BuroDeIngresos\DTOs\BuroWebhookDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoProfileDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoEmploymentsDTO;
use Illuminate\Support\Facades\Log;

abstract class WebhookAction
{
    protected BuroWebhookDTO $webhook;
    protected array $webhookRaw;
    protected ?CandidatoProfileDTO $profile;
    protected ?CandidatoEmploymentsDTO $employment;
    protected ?int $candidatoId;

    public function __construct(
        BuroWebhookDTO $webhook,
        ?CandidatoProfileDTO $profile = null,
        ?CandidatoEmploymentsDTO $employment = null,
        ?int $candidatoId = null
    ) {
        $this->webhook = $webhook;
        $this->profile = $profile;
        $this->employment = $employment;
        $this->candidatoId = $candidatoId;

    }

    /**
     * Ejecuta la acción
     */
    abstract public function execute(): void;

    /**
     * Obtiene el candidato ID (lazy loading)
     */
    protected function getCandidatoId(): int
    {
        if ($this->candidatoId !== null) {
            return $this->candidatoId;
        }

        $registro = RegistrosPalenca::getLastByCurp($this->webhook->identifier);
        
        if (!$registro) {
            throw new \Exception("No se encontró registro para CURP: {$this->webhook->identifier}");
        }

        $this->candidatoId = $registro->Candidato;
        return $this->candidatoId;
    }

    /**
     * Log helper
     */
    protected function log(string $message, array $context = []): void
    {
        Log::channel('buro_ingreso')->info("[{$this->webhook->identifier}] {$message}", $context);
    }
}