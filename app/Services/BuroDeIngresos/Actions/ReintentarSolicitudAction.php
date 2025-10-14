<?php
// app/Services/BuroDeIngresos/Actions/ReintentarSolicitudAction.php

namespace App\Services\BuroDeIngresos\Actions;

use App\Services\BuroDeIngresos\BuroDeIngresosService;
use Illuminate\Support\Facades\Log;

class ReintentarSolicitudAction
{
    public function __construct(
        private readonly BuroDeIngresosService $buroService
    ) {}

    /**
     * Reintenta la solicitud de Buro de Ingreso
     * 
     * Corresponde a: Utils::enviarSolicitudBuroDeIngreso() del legacy
     */
    public function ejecutar(int $candidatoId, string $curp): array
    {
        try {
            Log::channel('buro_ingreso')->info('Reintentando solicitud', [
                'candidato_id' => $candidatoId,
                'curp' => $curp
            ]);

            // Crear nueva verificación (retry)
            $result = $this->buroService
                ->setCurp($curp)
                ->createVerification("retry_{$candidatoId}_" . now()->timestamp);

            if (!$result['success']) {
                throw new \Exception(
                    $result['message'] ?? 'Error desconocido al reintentar solicitud'
                );
            }

            Log::channel('buro_ingreso')->info('Reintento exitoso', [
                'candidato_id' => $candidatoId,
                'curp' => $curp,
                'verification_id' => $result['data']['id'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Error reintentando solicitud', [
                'candidato_id' => $candidatoId,
                'curp' => $curp,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}