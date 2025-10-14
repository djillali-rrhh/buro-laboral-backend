<?php

namespace App\Services\BuroDeIngresos;

use App\Services\BuroDeIngresos\Actions\{
    RetryAction,
    ProcessCandidatoDatos,
    ProcessCandidatoDatosExtra,
    ProcessCandidatoLaborales,
    ProcessDocumentosSA,
};
use App\Services\BuroDeIngresos\DTOs\BuroWebhookDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoEmploymentsDTO;
use App\Services\BuroDeIngresos\DTOs\CandidatoProfileDTO;
use Illuminate\Support\Facades\Log;
use App\Models\RegistrosPalenca;

class BuroDeIngresosWebhookService
{
    public function __construct(
        private readonly BuroDeIngresosService $buroService,
    ) {}

    /**
     * Log helper
     */
    private function log(string $identifier, string $message, array $context = []): void
    {
        Log::channel('buro_ingreso')->info("[{$identifier}] - {$message}", $context);
    }

    /**
    * Procesa el webhook recibido
    * @param array $payload
    * @return array
    */
    public function processWebhook(array $payload): array
    {
        $this->log($payload['identifier'] ?? 'unknown', 'Webhook recibido', ['payload' => $payload]);

        $webhook = new BuroWebhookDTO($payload);

        $palenca = RegistrosPalenca::getLastByCurp($webhook->identifier);
        if (!$palenca) {
            $this->log($webhook->identifier, 'No se encontró un registro de Palenca para este CURP');

            return ['message' => 'No se encontró un registro de Palenca para este CURP'];
        }

        $this->log($webhook->identifier, 'Registro de Palenca encontrado', ['registro_id' => $palenca->id]);

        if (!$webhook->isCompleted()) {
            $this->log($webhook->identifier, "Estado de verificación no es 'completed'", ['status' => $webhook->status]);
            $this->updatePalencaLog($palenca, $payload);
            return ['message' => "Estado de verificación no es 'completed'"];
        }

        if ($webhook->can_retry) {
            $retryAction = new RetryAction($webhook);
            $retryAction->execute();

            $this->log($webhook->identifier, 'Reintento de verificación de datos');
            return ['message' => 'Reintento de verificación iniciado'];
        }

        if (!$webhook->data_available) {
            $this->log($webhook->identifier, 'La verificación no encontró datos');
            return ['message' => 'Datos no disponibles'];
        }

        $employment = null;
        $rawEmployment = null;
        if ($webhook->hasEntity(BuroWebhookDTO::ENTITY_EMPLOYMENT)) {
            $this->log($webhook->identifier, 'Datos de empleo disponibles');
            $rawEmployment = $this->buroService
                ->setCurp($webhook->identifier)
                ->getEmployments();

            if ($rawEmployment["http_code"] === 200) {
                $employment = new CandidatoEmploymentsDTO($rawEmployment["data"]);
            } else {
                $this->log($webhook->identifier, 'Error al obtener datos de empleo', ['error' => $rawEmployment['error']]);
            }
        }


        $profile = null;
        $rawProfile = null;
        if ($webhook->hasEntity(BuroWebhookDTO::ENTITY_PROFILE)) {
            $this->log($webhook->identifier, 'Datos de perfil disponibles');
            $rawProfile = $this->buroService
                ->setCurp($webhook->identifier)
                ->getProfile();

            if ($rawProfile["http_code"] === 200) {
                $profile = new CandidatoProfileDTO($rawProfile["data"]);
            } else {
                $this->log($webhook->identifier, 'Error al obtener datos de perfil', ['error' => $rawProfile['error']]);
            }
        }

        // Actualizar registro de Palenca con los datos completos
        $this->updatePalencaLog($palenca, $payload, $rawProfile, $rawEmployment);

        // Ejecutar el resto de las actions
        (new ProcessCandidatoDatos($webhook, $profile, $employment))->execute();
        (new ProcessCandidatoDatosExtra($webhook, $profile, $employment))->execute();
        (new ProcessCandidatoLaborales($webhook, $profile, $employment))->execute();
        (new ProcessDocumentosSA($webhook, $profile, $employment))->execute();

        // TODO: revisar si se debe conservar este bloque
        // Después de ejecutar las actions, validar si faltan datos
        $hasNSS = $profile && $profile->personal_info->nss;
        $hasHistory = $employment && !empty($employment->employment_history);

        if (!$hasNSS || !$hasHistory) {
            if (!$webhook->can_retry) {
                (new ProcessCandidatoDatosExtra($webhook, $profile, $employment))
                    ->markForSubdelegation();
            }
        }

        $this->log($webhook->identifier, 'Webhook procesado completamente');

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
