<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchByNameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'firstName' => 'required|string',
            'lastName' => 'sometimes|string',
            // Valida los formatos yyyy, yyyy-mm, yyyy-mm-dd
            'birthDate' => 'sometimes|string|regex:/^\d{4}(-\d{2}(-\d{2})?)?$/', 
            'states' => 'sometimes|array',
            'states.*' => [
                'string',
                Rule::in([
                    'ag', 'bc', 'bs', 'cc', 'cdmx', 'ch', 'cl', 'cm', 'cs', 'dg', 
                    'gr', 'gt', 'hg', 'ja', 'me', 'mn', 'ms', 'nl', 'nt', 'oc', 
                    'pl', 'qr', 'qt', 'si', 'sp', 'sr', 'tc', 'tl', 'tm', 'vz', 
                    'yn', 'zs'
                ])
            ],
            'areaOfLaw' => 'sometimes|array',
            'areaOfLaw.*' => [
                'string',
                Rule::in([
                    'administrativa', 'arrendamiento', 'civil', 'familiar', 
                    'fiscal', 'laboral', 'mercantil', 'penal'
                ])
            ],
        ];
    }
}