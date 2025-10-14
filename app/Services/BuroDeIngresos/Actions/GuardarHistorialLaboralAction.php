<?php
// app/Services/BuroDeIngresos/Actions/GuardarHistorialLaboralAction.php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosLaborales;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GuardarHistorialLaboralAction
{
    /**
     * Guarda el historial laboral del candidato
     * 
     * Corresponde a: Foreach que guarda en CandidatosLaborales del legacy
     */
    public function ejecutar(int $candidatoId, array $employmentHistory): int
    {
        if (empty($employmentHistory)) {
            Log::channel('buro_ingreso')->warning('Historial laboral vacío', [
                'candidato_id' => $candidatoId
            ]);
            return 0;
        }

        $empleosGuardados = 0;

        foreach ($employmentHistory as $empleo) {
            try {
                $fechaAlta = $this->parsearFecha($empleo['start_date'] ?? null);
                $fechaBaja = $this->parsearFecha($empleo['end_date'] ?? null);

                // Obtener siguiente renglon
                $maxRenglon = CandidatosLaborales::where('Candidato', $candidatoId)
                    ->max('Renglon') ?? 0;

                CandidatosLaborales::create([
                    'Candidato' => $candidatoId,
                    'Renglon' => $maxRenglon + 1,
                    'Empresa' => $empleo['employer'] ?? '',
                    'Fecha_Ingreso' => $fechaAlta,
                    'Fecha_Baja' => $fechaBaja,
                    'Salario' => $empleo['monthly_salary'] ?? 0,
                    'Estado_empleo' => $empleo['federal_entity'] ?? '',
                    'Empresa_isela' => $empleo['employer'] ?? '',
                    'Fecha_Ingreso_isela' => $fechaAlta,
                    'Fecha_Baja_isela' => $fechaBaja,
                    'Status' => 1
                ]);

                $empleosGuardados++;

            } catch (\Exception $e) {
                Log::channel('buro_ingreso')->error('Error guardando empleo', [
                    'candidato_id' => $candidatoId,
                    'empleo' => $empleo,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::channel('buro_ingreso')->info('Historial laboral guardado', [
            'candidato_id' => $candidatoId,
            'empleos_guardados' => $empleosGuardados,
            'empleos_totales' => count($employmentHistory)
        ]);

        return $empleosGuardados;
    }

    /**
     * Parsea una fecha a formato Y-m-d
     */
    private function parsearFecha(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->warning('Error parseando fecha', [
                'fecha' => $fecha,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}