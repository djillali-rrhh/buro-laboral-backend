<?php

namespace App\Services\BuroDeIngresos\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Representa el historial laboral del candidato de acuerdo a la api de Buro de Ingresos
 * https://developers.burodeingresos.com/reference/getemployments
 */
class CandidatoEmploymentsDTO
{
    public readonly string $identifier;
    public readonly DateTimeImmutable $updated_at;
    public readonly int $semanas_cotizadas;
    /** @var EmploymentRecordDTO[] */
    public readonly array $employment_history;

    public function __construct(public readonly array $data)
    {
        $this->identifier = $data['identifier'];
        $this->updated_at = new DateTimeImmutable($data['updated_at']);
        $this->semanas_cotizadas = (int) ($data['semanas_cotizadas'] ?? 0);

        $this->employment_history = array_map(
            fn ($item) => new EmploymentRecordDTO($item),
            $data['employment_history'] ?? []
        );
        
    }
}

class EmploymentRecordDTO
{
    public const INSTITUTION_IMSS = 'imss';
    public const INSTITUTION_ISSSTE = 'issste';

    public readonly string $employer;
    public readonly ?string $employer_registration;
    public readonly DateTimeImmutable $start_date;
    public readonly ?DateTimeImmutable $end_date;
    public readonly ?string $federal_entity;
    public readonly float $base_salary;
    public readonly float $monthly_salary;
    public readonly string $pdf_link;
    public readonly string $institution;

    public function __construct(array $data)
    {
        $this->employer = $data['employer'];
        $this->employer_registration = !empty($data['employer_registration']) ? $data['employer_registration'] : null;
        $this->start_date = new DateTimeImmutable($data['start_date']);
        $this->end_date = !empty($data['end_date']) ? new DateTimeImmutable($data['end_date']) : null;
        $this->federal_entity = !empty($data['federal_entity']) ? $data['federal_entity'] : null;
        $this->base_salary = (float) ($data['base_salary'] ?? 0);
        $this->monthly_salary = (float) ($data['monthly_salary'] ?? 0);
        $this->pdf_link = $data['pdf_link'];

        $this->institution = $data['institution'];
        $this->validateInstitution($this->institution);
    }

    private function validateInstitution(string $institution): void
    {
        $validInstitutions = [self::INSTITUTION_IMSS, self::INSTITUTION_ISSSTE];
        
        if (!in_array($institution, $validInstitutions, true)) {
            throw new InvalidArgumentException("Invalid institution: {$institution}");
        }
    }

    public function isIMSS(): bool
    {
        return $this->institution === self::INSTITUTION_IMSS;
    }

    public function isISSSTE(): bool
    {
        return $this->institution === self::INSTITUTION_ISSSTE;
    }

    public function isCurrentlyEmployed(): bool
    {
        return $this->end_date === null;
    }
}