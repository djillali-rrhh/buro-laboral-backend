<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la solicitud de obtención de trayectoria laboral.
 *
 * @package App\Http\Requests\Api\V1
 */
class ObtenerTrayectoriaRequest extends BaseFormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * Valida que se proporcione 'nss' o 'curp', pero no ambos.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nss' => ['required_without:curp', 'string', 'digits:11'],
            'curp' => ['required_without:nss', 'string', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/'],
        ];
    }
}
