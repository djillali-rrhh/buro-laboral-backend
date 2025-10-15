<?php

namespace App\Services\BuroDeIngresos;

use App\Services\BuroDeIngresos\Actions\RetryAction;
use App\Services\BuroDeIngresos\DTOs\BuroWebhookDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoEmploymentsDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoProfileDTO;
use Illuminate\Support\Facades\Log;
use App\Models\RegistrosPalenca;

class BuroDeIngresosWebhookService
{
    private array $commands = [];
    private array $payload;
    private ?BuroWebhookDTO $webhook = null;
    private ?CandidatoProfileDTO $profile = null;
    private ?CandidatoEmploymentsDTO $employment = null;
    private ?RegistrosPalenca $palenca = null;
    private ?array $rawProfile = null;
    private ?array $rawEmployment = null;

    public function __construct(
        private readonly BuroDeIngresosService $buroService,
    ) {}

    /**
     * Configura el payload para procesar
     */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Log helper
     */
    private function log(string $identifier, string $message, array $context = []): void
    {
        Log::channel('buro_ingreso')->info("[{$identifier}] - {$message}", $context);
    }

    /**
     * Agrega un comando a la cola de ejecución
     */
    public function addCommand(string $commandClass): self
    {
        $this->commands[] = $commandClass;
        return $this;
    }

    /**
     * Procesa el webhook y ejecuta todos los comandos
     */
    public function execute(): array
    {
        $this->log($this->payload['identifier'] ?? 'unknown', 'Webhook recibido', ['payload' => $this->payload]);

        $this->webhook = new BuroWebhookDTO($this->payload);

        $this->palenca = RegistrosPalenca::getLastByCurp($this->webhook->identifier);
        if (!$this->palenca) {
            $this->log($this->webhook->identifier, 'No se encontró un registro de Palenca para este CURP');
            return ['message' => 'No se encontró un registro de Palenca para este CURP'];
        }

        $this->log($this->webhook->identifier, 'Registro de Palenca encontrado', ['registro_id' => $this->palenca->id]);

        if (!$this->webhook->isCompleted()) {
            $this->log($this->webhook->identifier, "Estado de verificación no es 'completed'", ['status' => $this->webhook->status]);
            $this->updatePalencaLog($this->palenca, $this->payload);
            return ['message' => "Estado de verificación no es 'completed'"];
        }

        if ($this->webhook->can_retry) {
            $retryAction = new RetryAction();
            $retryAction->execute($this->webhook, $this->profile, $this->employment, $this->palenca->Candidato);
            
            $this->log($this->webhook->identifier, 'Reintento de verificación de datos');
            return ['message' => 'Reintento de verificación iniciado'];
        }

        if (!$this->webhook->data_available) {
            $this->log($this->webhook->identifier, 'La verificación no encontró datos');
            return ['message' => 'Datos no disponibles'];
        }

        // Obtener datos de empleo
        if ($this->webhook->hasEntity(BuroWebhookDTO::ENTITY_EMPLOYMENT)) {
            $this->log($this->webhook->identifier, 'Datos de empleo disponibles');
            $this->rawEmployment = $this->buroService
                ->setCurp($this->webhook->identifier)
                ->getEmployments();

            if ($this->rawEmployment["http_code"] === 200) {
                $this->employment = new CandidatoEmploymentsDTO($this->rawEmployment["data"]);
            } else {
                $this->log($this->webhook->identifier, 'Error al obtener datos de empleo', ['error' => $this->rawEmployment['error']]);
            }
        }

        // Obtener datos de perfil
        if ($this->webhook->hasEntity(BuroWebhookDTO::ENTITY_PROFILE)) {
            $this->log($this->webhook->identifier, 'Datos de perfil disponibles');
            $this->rawProfile = $this->buroService
                ->setCurp($this->webhook->identifier)
                ->getProfile();

            if ($this->rawProfile["http_code"] === 200) {
                $this->profile = new CandidatoProfileDTO($this->rawProfile["data"]);
            } else {
                $this->log($this->webhook->identifier, 'Error al obtener datos de perfil', ['error' => $this->rawProfile['error']]);
            }
        }

        // Actualizar registro de Palenca con los datos completos
        $this->updatePalencaLog($this->palenca, $this->payload, $this->rawProfile, $this->rawEmployment);

        // Ejecutar todos los comandos agregados
        foreach ($this->commands as $commandClass) {
            try {
                $command = new $commandClass();
                $command->execute($this->webhook, $this->profile, $this->employment, $this->palenca->Candidato);
            } catch (\Exception $e) {
                $this->log($this->webhook->identifier, "Error ejecutando comando {$commandClass}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        $this->log($this->webhook->identifier, 'Webhook procesado completamente');

        return ['message' => 'Webhook procesado completamente'];
    }

    /**
     * Actualiza el registro completo con todos los datos obtenidos
     * @param RegistrosPalenca $registro
     * @param array $webhook
     * @param array|null $profile
     * @param array|null $employment
     */
    private function updatePalencaLog(
        RegistrosPalenca $registro,
        array $webhook,
        ?array $profile = null,
        ?array $employment = null
    ): void {
        $accion = $webhook['status'] ?? 'unknown';

        switch ($accion) {
            case 'completed':
                $acceso = "SUCCESS";
                break;
            case 'failed':
                $acceso = 'FAILED';
                break;
            case 'in_progress':
                $acceso = 'IN PROGRESS';
                break;
        }

        // TODO: revisar si este mapeo es correcto
        // De acuerdo a legacy se deja esta mapeo
        $status = match ($accion) {
            'completed' => 1,
            'failed' => 0,
            'in_progress' => 0,
            default => 0,
        };

        $registro->update([
            'Estatus' => $status,
            'Acceso' => $acceso,
            'Respuesta_Json' => $webhook,
            'JsonProfile' => $profile,
            'JsonEmployment' => $employment,
            'JsonEmploymentHistory' => $employment['data']['employment_history'] ?? null,
        ]);

        $this->log($registro->CURP, 'Registro Palenca actualizado', [
            'registro_id' => $registro->id,
            'estatus' => $status
        ]);
    }
}