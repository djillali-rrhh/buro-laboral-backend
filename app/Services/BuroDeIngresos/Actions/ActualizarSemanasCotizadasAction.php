<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosDatosExtra;
use Illuminate\Support\Facades\Log;

class ActualizarSemanasCotizadasAction
{
    /**
     * Actualiza las semanas cotizadas del candidato
     * 
     * Corresponde a: Actualización de CandidatosDatosExtra del legacy
     */
    public function ejecutar(int $candidatoId, array $employmentData): void
    {
        if (empty($employmentData)) {
            $this->marcarSubdelegacion($candidatoId);
            return;
        }

        try {
            $quoted = $employmentData['semanas_cotizadas'] ?? 0;
            $discounted = $employmentData['discounted_weeks'] ?? 0;
            $reintegrated = $employmentData['reintegrated_weeks'] ?? 0;
            $total = $quoted - $discounted + $reintegrated;

            CandidatosDatosExtra::updateOrCreate(
                ['Candidato' => $candidatoId],
                [
                    'imss' => 'true',
                    'Semanas_Cotizadas' => $quoted,
                    'Semanas_Descontadas_IMSS' => $discounted,
                    'Semanas_Reintegradas' => $reintegrated,
                    'Total_Semanas_Cotizadas' => $total
                ]
            );

            Log::channel('buro_ingreso')->info('Semanas cotizadas actualizadas', [
                'candidato_id' => $candidatoId,
                'semanas_cotizadas' => $quoted,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Error actualizando semanas', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Marca al candidato para que acuda a subdelegación
     */
    public function marcarSubdelegacion(int $candidatoId): void
    {
        try {
            CandidatosDatosExtra::updateOrCreate(
                ['Candidato' => $candidatoId],
                [
                    'imss' => 'true',
                    'Numero_Empleos' => 'Acudir subdelegación',
                    'Semanas_Cotizadas' => 'Acudir subdelegación'
                ]
            );

            Log::channel('buro_ingreso')->warning('Candidato marcado para subdelegación', [
                'candidato_id' => $candidatoId
            ]);

        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Error marcando subdelegación', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}