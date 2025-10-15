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
    protected ?CandidatoProfileDTO $profile;
    protected ?CandidatoEmploymentsDTO $employment;
    protected ?int $candidatoId;

    /**
     * Ejecuta la acción con los datos proporcionados
     */
    public function execute(
        BuroWebhookDTO $webhook,
        ?CandidatoProfileDTO $profile = null,
        ?CandidatoEmploymentsDTO $employment = null,
        ?int $candidatoId = null
    ): void {
        $this->webhook = $webhook;
        $this->profile = $profile;
        $this->employment = $employment;
        $this->candidatoId = $candidatoId;

        $this->handle();
    }

    /**
     * Lógica principal de la acción
     */
    abstract protected function handle(): void;

    /**
     * Obtiene el candidato
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