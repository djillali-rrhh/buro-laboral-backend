<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Models\CandidatosDatos;

class ProcessCandidatoDatos extends WebhookAction
{
    public function execute(): void
    {
        if (!$this->profile || !$this->profile->personal_info->nss) {
            $this->log('No hay NSS para guardar en CandidatosDatos');
            return;
        }

        $candidatoId = $this->getCandidatoId();

        CandidatosDatos::updateOrCreate(
            ['Candidato' => $candidatoId],
            ['IMSS' => $this->profile->personal_info->nss]
        );

        $this->log('NSS guardado en CandidatosDatos', [
            'candidato_id' => $candidatoId,
            'nss' => $this->profile->personal_info->nss
        ]);
    }
}