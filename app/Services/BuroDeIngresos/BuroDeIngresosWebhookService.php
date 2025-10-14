<?php

namespace App\Services\BuroDeIngresos;

use App\Models\RegistrosPalenca;
use App\Services\BuroDeIngresos\Actions\{
    GuardarHistorialLaboralAction,
    ActualizarSemanasCotizadasAction,
    GuardarNSSAction,
    ReintentarSolicitudAction
};
use App\Services\BuroDeIngresos\DTOs\{WebhookPayloadDTO, CandidateDataDTO};
use Illuminate\Support\Facades\{DB, Log};

class BuroDeIngresosWebhookService
{
    public function __construct(
        private readonly BuroDeIngresosService $buroService,
        private readonly GuardarHistorialLaboralAction $guardarHistorial,
        private readonly ActualizarSemanasCotizadasAction $actualizarSemanas,
        private readonly GuardarNSSAction $guardarNSS,
        private readonly ReintentarSolicitudAction $reintentarSolicitud
    ) {}

    /**
     * Procesa el webhook recibido de Buro de Ingreso
     */
    public function procesarWebhook(array $payload): array
    {
        $inicioTotal = microtime(true);

        DB::beginTransaction();

        try {
            // 1. Validar y crear DTO del payload
            $inicio = microtime(true);
            $webhookDTO = new WebhookPayloadDTO($payload);
            $duracion = microtime(true) - $inicio;
            Log::channel('buro_ingreso')->info('Creación DTO del webhook', ['duracion_segundos' => round($duracion, 3)]);

            Log::channel('buro_ingreso')->info('Webhook recibido', [
                'curp' => $webhookDTO->curp,
                'status' => $webhookDTO->status,
                'canRetry' => $webhookDTO->canRetry
            ]);

            // 2. Buscar registro existente por CURP
            $inicio = microtime(true);
            $registro = RegistrosPalenca::where('CURP', $webhookDTO->curp)
                ->orderBy('id', 'desc')
                ->first();
            $duracion = microtime(true) - $inicio;
            Log::channel('buro_ingreso')->info('Búsqueda registro por CURP', [
                'duracion_segundos' => round($duracion, 3),
                'registro_id' => $registro->id ?? null
            ]);

            if (!$registro) {
                throw new \Exception("Registro no encontrado para CURP: {$webhookDTO->curp}");
            }

            // 3. Segunda llamada al API usando BuroDeIngresosService
            $inicio = microtime(true);
            $candidateData = $this->obtenerDatosCandidato($webhookDTO->curp);
            $candidateDTO = new CandidateDataDTO($candidateData);
            $duracion = microtime(true) - $inicio;
            Log::channel('buro_ingreso')->info('Obtener datos candidato', ['duracion_segundos' => round($duracion, 3)]);

            // 4. Actualizar registro con todos los JSONs
            $inicio = microtime(true);
            $this->actualizarRegistroConWebhook($registro, $webhookDTO, $candidateDTO, $payload);
            $duracion = microtime(true) - $inicio;
            Log::channel('buro_ingreso')->info('Actualización registro con webhook', ['duracion_segundos' => round($duracion, 3)]);

            // 5. Procesar según estado del webhook
            $inicio = microtime(true);
            $resultado = match($webhookDTO->status) {
                'completed' => $this->procesarCompletado($registro, $candidateDTO, $webhookDTO),
                'failed' => $this->procesarFallido($registro),
                'in_progress' => $this->procesarEnProgreso($registro),
                default => throw new \Exception("Estado desconocido: {$webhookDTO->status}")
            };
            $duracion = microtime(true) - $inicio;
            Log::channel('buro_ingreso')->info("Procesamiento estado '{$webhookDTO->status}'", ['duracion_segundos' => round($duracion, 3)]);

            DB::commit();

            // Duración total
            $duracionTotal = microtime(true) - $inicioTotal;
            Log::channel('buro_ingreso')->info('Webhook procesado exitosamente', [
                'curp' => $webhookDTO->curp,
                'resultado' => $resultado,
                'duracion_total_segundos' => round($duracionTotal, 3)
            ]);

            return $resultado;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('buro_ingreso')->error('Error procesando webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'curp' => $webhookDTO->curp ?? 'N/A'
            ]);

            throw $e;
        }
    }


    /**
     * Obtiene los datos completos del candidato (segunda llamada al API)
     * 
     * Corresponde a: BuroDeIngresoAPI->getCandidateData() del legacy
     */
    private function obtenerDatosCandidato(string $curp): array
    {
        Log::channel('buro_ingreso')->debug('Obteniendo datos completos del candidato', [
            'curp' => $curp
        ]);

        try {
            // 1. Obtener perfil (NSS y datos personales)
            $profile = $this->buroService
                ->setCurp($curp)
                ->getProfile();

            // 2. Obtener historial de empleos
            $employments = $this->buroService
                ->setCurp($curp)
                ->getEmployments();

            // Validar respuestas
            if (!$profile['success']) {
                Log::channel('buro_ingreso')->warning('Error obteniendo perfil', [
                    'curp' => $curp,
                    'http_code' => $profile['http_code'],
                    'message' => $profile['message'] ?? 'Unknown error'
                ]);
            }

            if (!$employments['success']) {
                Log::channel('buro_ingreso')->warning('Error obteniendo empleos', [
                    'curp' => $curp,
                    'http_code' => $employments['http_code'],
                    'message' => $employments['message'] ?? 'Unknown error'
                ]);
            }

            // Retornar estructura similar al legacy
            return [
                'profile' => $profile,
                'employments' => $employments,
            ];

        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Excepción obteniendo datos del candidato', [
                'curp' => $curp,
                'error' => $e->getMessage()
            ]);

            throw new \Exception(
                "Error obteniendo datos del candidato: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Actualiza el registro con los datos del webhook
     */
    private function actualizarRegistroConWebhook(
        RegistrosPalenca $registro,
        WebhookPayloadDTO $webhookDTO,
        CandidateDataDTO $candidateDTO,
        array $rawPayload
    ): void {
        $estatus = match($webhookDTO->status) {
            'completed' => 2,
            'in_progress' => 1,
            'failed' => 0,
            default => 0
        };

        $acceso = match($webhookDTO->status) {
            'completed' => 'SUCCESS',
            'in_progress' => 'IN PROGRESS',
            'failed' => 'FAILED',
            default => 'UNKNOWN'
        };

        $registro->update([
            'Estatus' => $estatus,
            'Acceso' => $acceso,
            'Respuesta_json' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE),
            'JsonProfile' => json_encode($candidateDTO->profile, JSON_UNESCAPED_UNICODE),
            'JsonEmployment' => json_encode($candidateDTO->employments, JSON_UNESCAPED_UNICODE),
            'JsonEmploymentHistory' => json_encode($candidateDTO->employmentHistory, JSON_UNESCAPED_UNICODE),
            'ModifiedAt' => now()
        ]);
    }

    /**
     * Procesa webhooks con estado "completed"
     */
    private function procesarCompletado(
        RegistrosPalenca $registro,
        CandidateDataDTO $data,
        WebhookPayloadDTO $webhook
    ): array {
        if ($data->tieneNSSyHistorial()) {
            Log::channel('buro_ingreso')->info('Escenario: NSS + Historial completo', [
                'curp' => $webhook->curp
            ]);
            return $this->procesarDatosCompletos($registro, $data);
        }

        if ($data->tieneNSSSinHistorial()) {
            Log::channel('buro_ingreso')->info('Escenario: NSS sin historial', [
                'curp' => $webhook->curp
            ]);
            return $this->procesarNSSSinHistorial($registro, $data, $webhook);
        }

        Log::channel('buro_ingreso')->warning('Escenario: Sin NSS ni historial', [
            'curp' => $webhook->curp
        ]);
        return $this->procesarSinDatos($registro, $webhook);
    }

    /**
     * Escenario A: Datos completos (NSS + Historial)
     */
    private function procesarDatosCompletos(
        RegistrosPalenca $registro,
        CandidateDataDTO $data
    ): array {
        $candidatoId = $registro->Candidato;

        $this->guardarHistorial->ejecutar($candidatoId, $data->employmentHistory);
        $this->actualizarSemanas->ejecutar($candidatoId, $data->employments['data'] ?? []);
        $this->guardarNSS->ejecutar($candidatoId, $data->getNSS());

        // TODO: Subir PDF a Wasabi (futuro)
        // $pdfUrl = $data->getPdfUrl();

        return [
            'scenario' => 'completo',
            'candidato_id' => $candidatoId,
            'nss' => $data->getNSS(),
            'empleos_guardados' => count($data->employmentHistory)
        ];
    }

    /**
     * Escenario B: Tiene NSS pero sin historial
     */
    private function procesarNSSSinHistorial(
        RegistrosPalenca $registro,
        CandidateDataDTO $data,
        WebhookPayloadDTO $webhook
    ): array {
        $candidatoId = $registro->Candidato;

        $this->guardarNSS->ejecutar($candidatoId, $data->getNSS());

        if ($webhook->canRetry) {
            $this->reintentarSolicitud->ejecutar($candidatoId, $webhook->curp);
            
            return [
                'scenario' => 'parcial_retry',
                'candidato_id' => $candidatoId,
                'nss' => $data->getNSS(),
                'action' => 'retry_solicitado'
            ];
        }

        $this->actualizarSemanas->marcarSubdelegacion($candidatoId);

        return [
            'scenario' => 'parcial_subdelegacion',
            'candidato_id' => $candidatoId,
            'nss' => $data->getNSS(),
            'action' => 'subdelegacion_marcada'
        ];
    }

    /**
     * Escenario C: Sin NSS ni historial
     */
    private function procesarSinDatos(
        RegistrosPalenca $registro,
        WebhookPayloadDTO $webhook
    ): array {
        $candidatoId = $registro->Candidato;

        if ($webhook->canRetry) {
            $this->reintentarSolicitud->ejecutar($candidatoId, $webhook->curp);
            
            return [
                'scenario' => 'sin_datos_retry',
                'candidato_id' => $candidatoId,
                'action' => 'retry_solicitado'
            ];
        }

        $this->actualizarSemanas->marcarSubdelegacion($candidatoId);

        return [
            'scenario' => 'sin_datos_subdelegacion',
            'candidato_id' => $candidatoId,
            'action' => 'subdelegacion_marcada'
        ];
    }

    /**
     * Procesa webhooks fallidos
     */
    private function procesarFallido(RegistrosPalenca $registro): array
    {
        $registro->update([
            'Estatus' => 0,
            'Acceso' => 'FAILED',
            'ModifiedAt' => now()
        ]);
        
        return [
            'scenario' => 'failed',
            'candidato_id' => $registro->Candidato
        ];
    }

    /**
     * Procesa webhooks en progreso
     */
    private function procesarEnProgreso(RegistrosPalenca $registro): array
    {
        $registro->update([
            'Estatus' => 1,
            'Acceso' => 'IN PROGRESS',
            'ModifiedAt' => now()
        ]);
        
        return [
            'scenario' => 'in_progress',
            'candidato_id' => $registro->Candidato
        ];
    }
}