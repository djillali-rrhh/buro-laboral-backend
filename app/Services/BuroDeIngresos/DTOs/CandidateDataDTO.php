<?php

namespace App\Services\BuroDeIngresos\DTOs;

class CandidateDataDTO
{
    public readonly array $profile;
    public readonly array $employments;
    public readonly array $employmentHistory;

    public function __construct(array $candidateData)
    {
        $this->profile = $candidateData['profile'] ?? [];
        $this->employments = $candidateData['employments'] ?? [];
        $this->employmentHistory = $this->employments['data']['employment_history'] ?? [];
    }

    /**
     * Verifica si tiene NSS y historial laboral completo
     */
    public function tieneNSSyHistorial(): bool
    {
        return $this->profileSuccess()
            && !empty($this->getNSS())
            && $this->employmentsSuccess()
            && !empty($this->employmentHistory);
    }

    /**
     * Verifica si tiene NSS pero sin historial
     */
    public function tieneNSSSinHistorial(): bool
    {
        return $this->profileSuccess()
            && !empty($this->getNSS())
            && !$this->employmentsSuccess();
    }

    /**
     * Obtiene el NSS del perfil
     */
    public function getNSS(): ?string
    {
        return $this->profile['data']['personal_info']['nss'] ?? null;
    }

    /**
     * Obtiene la URL del PDF del historial laboral
     */
    public function getPdfUrl(): ?string
    {
        return $this->employmentHistory[0]['pdf_link'] ?? null;
    }

    /**
     * Verifica si el perfil se obtuvo exitosamente
     */
    private function profileSuccess(): bool
    {
        return ($this->profile['success'] ?? false) === true;
    }

    /**
     * Verifica si los datos de empleos se obtuvieron exitosamente
     */
    private function employmentsSuccess(): bool
    {
        return ($this->employments['success'] ?? false) === true;
    }
}