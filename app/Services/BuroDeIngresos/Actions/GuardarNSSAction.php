<?php
// app/Services/BuroDeIngresos/Actions/GuardarNSSAction.php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosDatos;
use Illuminate\Support\Facades\Log;

class GuardarNSSAction
{
    /**
     * Guarda el NSS del candidato
     * 
     * Corresponde a: actualizarImss() del legacy
     */
    public function ejecutar(int $candidatoId, ?string $nss): void
    {
        if (empty($nss)) {
            Log::channel('buro_ingreso')->warning('NSS vacío, no se guardará', [
                'candidato_id' => $candidatoId
            ]);
            return;
        }

        try {
            $updated = CandidatosDatos::where('Candidato', $candidatoId)
                ->update(['IMSS' => $nss]);

            if ($updated) {
                Log::channel('buro_ingreso')->info('NSS guardado exitosamente', [
                    'candidato_id' => $candidatoId,
                    'nss' => substr($nss, 0, 4) . '...' . substr($nss, -2) // Ofuscar en logs
                ]);
            } else {
                Log::channel('buro_ingreso')->warning('No se actualizó NSS (candidato no existe?)', [
                    'candidato_id' => $candidatoId
                ]);
            }

        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Error guardando NSS', [
                'candidato_id' => $candidatoId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}