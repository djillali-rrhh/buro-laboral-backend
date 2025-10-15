<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosLaborales;

class ProcessCandidatoLaborales extends WebhookAction
{
    protected function handle(): void
    {
        if (!$this->employment || empty($this->employment->employment_history)) {
            $this->log('No hay historial laboral para guardar');
            return;
        }

        $candidatoId = $this->getCandidatoId();
        $savedCount = 0;

        foreach ($this->employment->employment_history as $record) {
            $maxRenglon = CandidatosLaborales::getMaxRenglon($candidatoId);
            
            CandidatosLaborales::create([
                'Candidato' => $candidatoId,
                'Renglon' => $maxRenglon + 1,
                'Empresa' => $record->employer,
                'Empresa_isela' => $record->employer,
                'Fecha_Ingreso' => $record->start_date->format('Y-m-d'),
                'Fecha_Baja' => $record->end_date?->format('Y-m-d'),
                'Fecha_Ingreso_isela' => $record->start_date->format('Y-m-d'),
                'Fecha_Baja_isela' => $record->end_date?->format('Y-m-d'),
                'Salario' => $record->monthly_salary,
                'Estado_empleo' => $record->federal_entity,
                'Status' => 1,
            ]);

            $savedCount++;
        }

        $this->log('Historial laboral guardado', [
            'candidato_id' => $candidatoId,
            'empleos_guardados' => $savedCount
        ]);
    }
}