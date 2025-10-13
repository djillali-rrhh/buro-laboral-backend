<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchByCurpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Lo dejamos en true para permitir que cualquier usuario autenticado la use.
        // Puedes agregar tu propia lógica de autorización aquí si es necesario.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'curp' => [
                'required',
                'string',
                // Expresión regular exacta de la documentación
                'regex:/^[A-Z]{4}\d{6}[HMX][A-Z]{2}[A-Z0-9]{3}[0-9A-Z]\d$/'
            ],
            'ageOfMajority' => 'sometimes|boolean',
            'states' => 'sometimes|array',
            'states.*' => [ // Valida cada elemento dentro del array 'states'
                'string',
                Rule::in([
                    'ag', 'bc', 'bs', 'cc', 'cdmx', 'ch', 'cl', 'cm', 'cs', 'dg', 
                    'gr', 'gt', 'hg', 'ja', 'me', 'mn', 'ms', 'nl', 'nt', 'oc', 
                    'pl', 'qr', 'qt', 'si', 'sp', 'sr', 'tc', 'tl', 'tm', 'vz', 
                    'yn', 'zs'
                ])
            ],
            'areaOfLaw' => 'sometimes|array',
            'areaOfLaw.*' => [ // Valida cada elemento dentro del array 'areaOfLaw'
                'string',
                Rule::in([
                    'administrativa', 'arrendamiento', 'civil', 'familiar', 
                    'fiscal', 'laboral', 'mercantil', 'penal'
                ])
            ],
        ];
    }
}