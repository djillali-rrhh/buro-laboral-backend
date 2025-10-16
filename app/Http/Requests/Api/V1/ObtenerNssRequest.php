<?php

namespace App\Http\Requests\Api\V1;

/**
 * Form Request para validar la solicitud de obtención de NSS.
 * Hereda de BaseFormRequest para un manejo de errores centralizado.
 *
 * @package App\Http\Requests\Api\V1
 */
class ObtenerNssRequest extends BaseFormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'curp' => ['required', 'string', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/'],
        ];
    }
}

