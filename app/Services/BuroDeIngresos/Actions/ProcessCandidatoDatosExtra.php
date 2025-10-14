<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosDatosExtra;

class ProcessCandidatoDatosExtra extends WebhookAction
{
    public function execute(): void
    {
        $candidatoId = $this->getCandidatoId();

        // Datos base a actualizar siempre
        $dataToUpdate = [
            'IMSS' => 'true',
        ];

        // Si hay datos de employment
        if ($this->employment) {
            $dataToUpdate['Semanas_Cotizadas'] = $this->employment->semanas_cotizadas;
            $dataToUpdate['Numero_Empleos'] = count($this->employment->employment_history ?? []);
            
            $this->log('Semanas cotizadas actualizadas', [
                'candidato_id' => $candidatoId,
                'semanas' => $this->employment->semanas_cotizadas,
                'numero_empleos' => $dataToUpdate['Numero_Empleos'] ?? 0
            ]);
        }

        CandidatosDatosExtra::updateOrCreate(
            ['Candidato' => $candidatoId],
            $dataToUpdate
        );

        $this->log('CandidatosDatosExtra actualizado', [
            'candidato_id' => $candidatoId,
            'datos' => $dataToUpdate
        ]);
    }

    /**
     * Marca al candidato para ir a subdelegación
     */
    public function markForSubdelegation(): void
    {
        $candidatoId = $this->getCandidatoId();

        CandidatosDatosExtra::updateOrCreate(
            ['Candidato' => $candidatoId],
            [
                'Numero_Empleos' => 'Acudir subdelegación',
                'Semanas_Cotizadas' => 'Acudir subdelegación',
                'IMSS' => 'true',
            ]
        );

        $this->log('Candidato marcado para subdelegación', [
            'candidato_id' => $candidatoId
        ]);
    }
}