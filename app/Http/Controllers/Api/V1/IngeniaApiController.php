<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RegistrosIngeniaApi;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class IngeniaApiController extends Controller
{
    use ApiResponse;

    /**
     * Consulta información por CURP.
     */
    public function consultarPorCurp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'curp' => 'required|string|size:18',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos de entrada inválidos.', 422, $validator->errors());
        }

        $curp = $request->input('curp');
        $payload = ['curp' => $curp];
        $tipoConsulta = 'por_curp';
        $baseUrl = config('services.ingenia.base_uri');
        
        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('ingenia_api')->info("Iniciando consulta [{$tipoConsulta}]", $payload);
            
            $response = Http::post($baseUrl . '/sdrfcc', $payload);
            $responseData = $response->json();

            if ($response->successful() && ($responseData['success'] ?? false)) {
                $status = 'exitoso';
                return $this->successResponse($responseData, 'Consulta por CURP realizada con éxito.');
            }
            
            $status = 'fallido';
            return $this->errorResponse($responseData['message'] ?? 'Error en la API de Ingenia.', $response->status(), $responseData);

        } catch (Throwable $e) {
            $status = 'error_conexion';
            Log::channel('ingenia_api')->error("Error de conexión en [{$tipoConsulta}]", ['error' => $e->getMessage()]);
            $responseData = ['error' => $e->getMessage()];
            return $this->errorResponse('Error interno al comunicarse con el servicio.', 500);
        } finally {
            $this->logApiCall($curp, $tipoConsulta, $payload, $responseData, $status);
        }
    }

    /**
     * Obtiene CURP por datos personales.
     */
    public function obtenerCurpPorDatos(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'required|string|max:100',
            'dia_nacimiento' => 'required|integer|between:1,31',
            'mes_nacimiento' => 'required|integer|between:1,12',
            'anio_nacimiento' => 'required|integer|digits:4',
            'sexo' => 'required|string|in:HOMBRE,MUJER',
            'estado_nacimiento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Datos de entrada inválidos.', 422, $validator->errors());
        }
        
        $validatedData = $validator->validated();

        $sexoParaApi = ($validatedData['sexo'] === 'HOMBRE') ? 'H' : 'M';

        $diaParaApi = str_pad($validatedData['dia_nacimiento'], 2, '0', STR_PAD_LEFT);
        $mesParaApi = str_pad($validatedData['mes_nacimiento'], 2, '0', STR_PAD_LEFT);

        $payload = [
            "nombre" => $validatedData['nombre'],
            "paterno" => $validatedData['apellido_paterno'],
            "materno" => $validatedData['apellido_materno'],
            "dia" => $diaParaApi,
            "mes" => $mesParaApi,
            "anio" => $validatedData['anio_nacimiento'],
            "estado" => $this->mapEstado($validatedData['estado_nacimiento']),
            "sexo" => $sexoParaApi,
        ];
        $tipoConsulta = 'por_datos';
        $baseUrl = config('services.ingenia.base_uri');

        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('ingenia_api')->info("Iniciando consulta [{$tipoConsulta}]", $payload);

            $response = Http::post($baseUrl . '/scrfcd', $payload);
            $responseData = $response->json();

            if ($response->successful() && ($responseData['success'] ?? false)) {
                $status = 'exitoso';
                return $this->successResponse($responseData, 'Obtención de CURP por datos realizada con éxito.');
            }

            $status = 'fallido';
            return $this->errorResponse($responseData['message'] ?? 'Error en la API de Ingenia.', $response->status(), $responseData);

        } catch (Throwable $e) {
            $status = 'error_conexion';
            Log::channel('ingenia_api')->error("Error de conexión en [{$tipoConsulta}]", ['error' => $e->getMessage()]);
            $responseData = ['error' => $e->getMessage()];
            return $this->errorResponse('Error interno al comunicarse con el servicio.', 500);
        } finally {
            $curp = $responseData['curp']['curp'] ?? null;
            $this->logApiCall($curp, $tipoConsulta, $payload, $responseData, $status);
        }
    }

    /**
     * Registra la llamada a la API en la base de datos.
     */
    private function logApiCall(?string $curp, string $tipoConsulta, array $requestPayload, ?array $responsePayload, string $status): void
    {
        try {
            RegistrosIngeniaApi::create([
                'curp' => $curp,
                'tipo_consulta' => $tipoConsulta,
                'payload_request' => $requestPayload,
                'payload_response' => $responsePayload,
                'estatus' => $status,
            ]);
        } catch (Throwable $e) {
            Log::channel('ingenia_api')->critical("¡FALLO AL GUARDAR LOG DE AUDITORÍA!", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Mapea el nombre del estado a su abreviatura.
     */
    private function mapEstado(string $estadoNombre): ?string
    {
        $estados = [
            "Aguascalientes" => "AS", "Baja California" => "BC", "Baja California Sur" => "BS",
            "Campeche" => "CC", "Chiapas" => "CS", "Chihuahua" => "CH", "Ciudad de México" => "DF",
            "Coahuila" => "CL", "Colima" => "CM", "Durango" => "DG", "Estado de México" => "MC",
            "Guanajuato" => "GT", "Guerrero" => "GR", "Hidalgo" => "HG", "Jalisco" => "JC",
            "Michoacán de Ocampo" => "MN", "Morelos" => "MS", "Nayarit" => "NT", "Nuevo León" => "NL",
            "Oaxaca" => "OC", "Puebla" => "PL", "Querétaro" => "QT", "Quintana Roo" => "QR",
            "San Luis Potosí" => "SP", "Sinaloa" => "SL", "Sonora" => "SR", "Tabasco" => "TC",
            "Tamaulipas" => "TS", "Tlaxcala" => "TL", "Veracruz de Ignacio de la Llave" => "VZ",
            "Yucatán" => "YN", "Zacatecas" => "ZS", "Nacido en el Extranjero" => "NE",
        ];
        return $estados[$estadoNombre] ?? null;
    }
}