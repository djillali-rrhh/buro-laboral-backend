<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Clase base para los FormRequest de la API.
 * Centraliza el manejo de respuestas de validación fallidas para que todas
 * las solicitudes de la API devuelvan un JSON de error consistente.
 *
 * @package App\Http\Requests\Api\V1
 */
class BaseFormRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Maneja un intento de validación fallido.
     *
     * Este método se activa automáticamente cuando la validación falla,
     * lanzando una excepción con una respuesta JSON formateada usando ApiResponse trait.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
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
                $validator->errors()->toArray()
            )
        );
    }
}
