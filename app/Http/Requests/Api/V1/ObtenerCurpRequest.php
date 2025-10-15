<?php

namespace App\Http\Requests\Api\V1;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request para validar y transformar los datos de la petición para obtener una CURP.
 *
 * Se encarga de validar los datos personales del candidato y de transformar
 * los campos 'estado' y 'sexo' al formato de abreviatura requerido antes de
 * pasarlos al controlador.
 *
 * @package App\Http\Requests\Api\V1
 */
class ObtenerCurpRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Determina si el usuario está autorizado para realizar esta petición.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Se permite el acceso a todos los usuarios para este endpoint.
        return true;
    }

    /**
     * Prepara los datos para la validación.
     *
     * Este método se ejecuta antes de las reglas de validación. Transforma los
     * datos de entrada (nombres completos de estado y sexo) al formato de
     * abreviatura requerido por la API de Ingenia.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Se asegura de que la transformación ocurra antes de validar
        if ($this->has('estado')) {
            $this->merge([
                'estado' => $this->mapEstado($this->input('estado')),
            ]);
        }
        if ($this->has('sexo')) {
            $this->merge([
                'sexo' => $this->mapSexo($this->input('sexo')),
            ]);
        }
    }

    /**
     * Obtiene las reglas de validación que se aplican a la petición.
     *
     * Las reglas validan el formato final de los datos (abreviaturas) después
     * de que han sido transformados por `prepareForValidation`.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre'   => 'required|string|max:100',
            'paterno'  => 'required|string|max:100',
            'materno'  => 'required|string|max:100',
            'dia'      => 'required|string',
            'mes'      => 'required|string',
            'anio'     => 'required|integer|digits:4',
            'sexo'     => 'required|string|size:1|in:H,M,X',
            'estado'   => 'required|string|size:2',
        ];
    }

    /**
     * Maneja un intento de validación fallido.
     *
     * Sobrescribe el comportamiento por defecto para asegurar que siempre se
     * devuelva una respuesta JSON con los detalles del error y el código HTTP 422.
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
                422, // Código HTTP 422: Unprocessable Entity
                $validator->errors()
            )
        );
    }

    /**
     * Mapea el nombre del estado o su abreviatura al formato de dos letras.
     *
     * Acepta tanto el nombre completo (ej. "Tamaulipas") como la abreviatura (ej. "TS"),
     * y es insensible a mayúsculas y minúsculas.
     *
     * @param string|null $estadoNombre El nombre o abreviatura del estado.
     * @return string|null La abreviatura de dos letras o null si no se encuentra.
     */
    private function mapEstado(?string $estadoNombre): ?string
    {
        if (!$estadoNombre) {
            return null;
        }

        $estados = [
            "Aguascalientes" => "AS",
            "Baja California" => "BC",
            "Baja California Sur" => "BS",
            "Campeche" => "CC",
            "Chiapas" => "CS",
            "Chihuahua" => "CH",
            "Ciudad de México" => "DF",
            "Coahuila" => "CL",
            "Colima" => "CM",
            "Durango" => "DG",
            "Estado de México" => "MC",
            "Guanajuato" => "GT",
            "Guerrero" => "GR",
            "Hidalgo" => "HG",
            "Jalisco" => "JC",
            "Michoacán de Ocampo" => "MN",
            "Morelos" => "MS",
            "Nayarit" => "NT",
            "Nuevo León" => "NL",
            "Oaxaca" => "OC",
            "Puebla" => "PL",
            "Querétaro" => "QT",
            "Quintana Roo" => "QR",
            "San Luis Potosí" => "SP",
            "Sinaloa" => "SL",
            "Sonora" => "SR",
            "Tabasco" => "TC",
            "Tamaulipas" => "TS",
            "Tlaxcala" => "TL",
            "Veracruz de Ignacio de la Llave" => "VZ",
            "Yucatán" => "YN",
            "Zacatecas" => "ZS",
            "Nacido en el Extranjero" => "NE",
        ];

        // 1. Primero, verifica si la entrada ya es una abreviatura válida.
        if (in_array(strtoupper($estadoNombre), array_values($estados))) {
            return strtoupper($estadoNombre);
        }

        // 2. Si no es una abreviatura, intenta mapear el nombre completo.
        $inputNormalizado = mb_strtolower($estadoNombre, 'UTF-8');
        foreach ($estados as $nombreCorrecto => $abreviatura) {
            if (mb_strtolower($nombreCorrecto, 'UTF-8') === $inputNormalizado) {
                return $abreviatura;
            }
        }

        return null; // Devuelve null si no se encuentra ninguna coincidencia
    }

    /**
     * Mapea el sexo o su abreviatura al formato de una letra.
     *
     * Acepta tanto la palabra completa (ej. "Hombre") como la letra (ej. "H"),
     * y es insensible a mayúsculas y minúsculas.
     *
     * @param string|null $sexo El sexo a mapear.
     * @return string|null La abreviatura de una letra o null si no es un valor válido.
     */
    private function mapSexo(?string $sexo): ?string
    {
        if (!$sexo) {
            return null;
        }

        // Normaliza la entrada a mayúsculas para comparar
        return match (strtoupper($sexo)) {
            'HOMBRE', 'H' => 'H',
            'MUJER', 'M'  => 'M',
            'OTRO', 'X'   => 'X',
            default      => null,
        };
    }
}

