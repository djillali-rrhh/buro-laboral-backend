<?php

namespace App\Services\BuroDeIngresos\Actions;

use App\Services\BuroDeIngresos\BuroDeIngresosService;

class RetryAction extends WebhookAction
{
    protected function handle(): void
    {
        $this->log('Iniciando reintento de verificación');

        $service = new BuroDeIngresosService();
        $service->setCurp($this->webhook->identifier);
        
        $verification = $service->createVerification();

        if (!$verification['success']) {
            $this->log('Error al crear verificación de reintento', [
                'error' => $verification['message'] ?? 'Unknown error'
            ]);
            throw new \Exception("Failed to create retry verification");
        }

        $this->log('Verificación de reintento creada exitosamente', [
            'verification_id' => $verification['data']['id'] ?? null
        ]);
    }
}