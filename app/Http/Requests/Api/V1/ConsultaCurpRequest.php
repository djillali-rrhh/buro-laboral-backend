<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultaCurpRequest extends FormRequest
{
    use ApiResponse;

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
            'curp' => 'required|string|size:18',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * Sobrescribe el comportamiento por defecto para asegurar que siempre se
     * devuelva una respuesta JSON con los detalles del error.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse(
                'Datos de entrada inválidos.',
                422,
                $validator->errors()
            )
        );
    }
}

