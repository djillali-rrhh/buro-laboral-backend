<?php

namespace App\Services\BuroDeIngresos\Actions;

class ProcessDocumentosSA extends WebhookAction
{
    protected function handle(): void
    {
        if (!$this->employment || empty($this->employment->employment_history)) {
            $this->log('No hay historial laboral, no se puede guardar PDF');
            return;
        }

        $firstEmployment = $this->employment->employment_history[0] ?? null;
        
        if (!$firstEmployment || !$firstEmployment->pdf_link) {
            $this->log('No hay PDF link disponible');
            return;
        }

        $candidatoId = $this->getCandidatoId();
        
        // TODO: Implementar subida a Wasabi
        // Por ahora solo guardamos la referencia al PDF externo
        $this->log('PDF disponible para descarga', [
            'candidato_id' => $candidatoId,
            'pdf_link' => $firstEmployment->pdf_link
        ]);

        // Placeholder para futura implementación
        // $wasabiUrl = $this->uploadToWasabi($firstEmployment->pdf_link, $candidatoId);
        // 
        // if ($wasabiUrl) {
        //     DocumentosSA::create([
        //         'candidato' => $candidatoId,
        //         'documento' => 'registro_patronal',
        //         'url' => $wasabiUrl,
        //         'filepath' => "registro_patronal/{$candidatoId}/{$candidatoId}.pdf",
        //     ]);
        // }
    }

    /**
     * TODO: Implementar subida a Wasabi
     */
    private function uploadToWasabi(string $pdfUrl, int $candidatoId): ?string
    {
        // Implementación futura
        return null;
    }
}