<?php

namespace App\Services\BuroDeIngresos\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Representa el perfil del candidato de acuerdo a la api de Buro de Ingresos
 * https://developers.burodeingresos.com/reference/getprofile
 */
class CandidatoProfileDTO
{
    // Constantes para employment status
    public const EMPLOYMENT_STATUS_EMPLOYED = 'employed';
    public const EMPLOYMENT_STATUS_UNEMPLOYED = 'unemployed';
    public const EMPLOYMENT_STATUS_UNKNOWN = 'unknown';

    public readonly string $identifier;
    public readonly DateTimeImmutable $updated_at;
    public readonly PersonalInfoDTO $personal_info;
    public readonly string $employment_status;
    public readonly AddressDTO $address;

    public function __construct(public readonly array $data)
    {
        $this->identifier = $data['identifier'];
        $this->updated_at = new DateTimeImmutable($data['updated_at']);
        $this->personal_info = new PersonalInfoDTO($data['personal_info']);
        $this->address = new AddressDTO($data['address']);
        
        $this->employment_status = $data['employment_status'] ?? self::EMPLOYMENT_STATUS_UNKNOWN;
        $this->validateEmploymentStatus($this->employment_status);
    }

    private function validateEmploymentStatus(string $status): void
    {
        $validStatuses = [
            self::EMPLOYMENT_STATUS_EMPLOYED,
            self::EMPLOYMENT_STATUS_UNEMPLOYED,
            self::EMPLOYMENT_STATUS_UNKNOWN
        ];
        
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid employment status: {$status}");
        }
    }

    public function isEmployed(): bool
    {
        return $this->employment_status === self::EMPLOYMENT_STATUS_EMPLOYED;
    }

    public function isUnemployed(): bool
    {
        return $this->employment_status === self::EMPLOYMENT_STATUS_UNEMPLOYED;
    }
}

class PersonalInfoDTO
{
    public readonly string $first_name;
    public readonly string $last_name;
    public readonly string $curp;
    public readonly ?string $nss;
    public readonly ?string $rfc;
    public readonly ?string $phone;
    public readonly ?string $email;

    public function __construct(array $data)
    {
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'];
        $this->curp = $data['curp'];

        $this->nss = !empty($data['nss']) ? $data['nss'] : null;
        $this->rfc = !empty($data['rfc']) ? $data['rfc'] : null;
        $this->phone = !empty($data['phone']) ? $data['phone'] : null;
        $this->email = !empty($data['email']) ? $data['email'] : null;
    }
}


class AddressDTO
{
    public readonly string $street;
    public readonly string $neighborhood;
    public readonly string $municipality;
    public readonly string $state;
    public readonly string $zip_code;

    public function __construct(array $data)
    {
        $this->street = $data['street'] ?? '';
        $this->neighborhood = $data['neighborhood'] ?? '';
        $this->municipality = $data['municipality'] ?? '';
        $this->state = $data['state'] ?? '';
        $this->zip_code = $data['zip_code'] ?? '';
    }
    
    public function isEmpty(): bool
    {
        return empty($this->street) 
            && empty($this->neighborhood) 
            && empty($this->municipality) 
            && empty($this->state) 
            && empty($this->zip_code);
    }
}