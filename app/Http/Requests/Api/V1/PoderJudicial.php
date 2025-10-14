<?php

namespace App\Http\Requests\Api\V1\PoderJudicial;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para validar la solicitud de búsqueda al PoderJudicialController.
 * Centraliza las reglas de validación para el endpoint de búsqueda,
 * asegurando que solo datos válidos lleguen al servicio.
 */
class SearchRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Se establece en true, asumiendo que la autorización se manejará
        // a través de middleware en la ruta (ej. Sanctum).
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'curp' => [
                'required',
                'string',
                'regex:/^[A-Z]{4}\d{6}[HMX][A-Z]{2}[A-Z0-9]{3}[0-9A-Z]\d$/'
            ],
            'state' => ['required', 'string', 'max:50']
        ];
    }

    /**
     * Personaliza los mensajes de error de validación.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'curp.required' => 'El campo CURP es obligatorio.',
            'curp.regex' => 'El formato del CURP no es válido.',
            'state.required' => 'El campo de estado es obligatorio.',
        ];
    }
}
