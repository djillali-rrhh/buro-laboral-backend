<?php

namespace App\Services;

use App\Models\RegistrosIngeniaApi;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para gestionar la comunicación con Ingenia API.
 *
 * Encapsula la lógica de las llamadas a la API y el registro de auditoría.
 */
class IngeniaApiService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ingenia.base_uri');
    }

    /**
     * Consulta la información de un candidato por su CURP.
     *
     * @param string $curp
     * @return array
     */
    public function consultarPorCurp(string $curp): array
    {
        $payload = ['curp' => $curp];
        $tipoConsulta = 'por_curp';
        $endpoint = '/sdrfcc';
        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('ingenia_api')->info("Iniciando consulta [{$tipoConsulta}]", $payload);

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);
            
            $response->throw();

            $responseData = $response->json();

            if ($responseData['success'] ?? false) {
                $status = 'exitoso';
                return ['success' => true, 'data' => $responseData, 'status' => $response->status()];
            }
            
            $status = 'fallido';
            $message = $responseData['message'] ?? 'La API de Ingenia devolvió una respuesta fallida.';
            return ['success' => false, 'message' => $message, 'status' => $response->status(), 'data' => $responseData];

        } catch (Throwable $e) {
            $status = 'error_conexion';
            $statusCode = 500;
            
            if ($e instanceof RequestException) {
                $responseData = $e->response->json() ?? ['error_message' => $e->response->body()];
                $statusCode = $e->response->status();
            } else {
                $responseData = ['error' => $e->getMessage()];
            }

            Log::channel('ingenia_api')->error("Error en la petición a Ingenia [{$tipoConsulta}]", [
                'error' => $e->getMessage(),
                'response_body' => $responseData
            ]);

            return ['success' => false, 'message' => 'Error al comunicarse con el servicio de Ingenia.', 'status' => $statusCode, 'data' => $responseData];
        } finally {
            $this->logApiCall($curp, $tipoConsulta, $payload, $responseData, $status);
        }
    }

    /**
     * Obtiene el CURP de un candidato a partir de sus datos personales.
     *
     * @param array $data Los datos validados del request.
     * @return array
     */
    public function obtenerCurpPorDatos(array $data): array
    {
        $payload = [
            "nombre" => $data['nombre'],
            "paterno" => $data['apellido_paterno'],
            "materno" => $data['apellido_materno'],
            "dia" => str_pad($data['dia_nacimiento'], 2, '0', STR_PAD_LEFT),
            "mes" => str_pad($data['mes_nacimiento'], 2, '0', STR_PAD_LEFT),
            "anio" => $data['anio_nacimiento'],
            "estado" => $this->mapEstado($data['estado_nacimiento']),
            "sexo" => ($data['sexo'] === 'HOMBRE') ? 'H' : 'M',
        ];
        $tipoConsulta = 'por_datos';
        $endpoint = '/scrfcd';
        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('ingenia_api')->info("Iniciando consulta [{$tipoConsulta}]", $payload);

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . $endpoint, $payload);

            $response->throw();

            $responseData = $response->json();

            if ($responseData['success'] ?? false) {
                $status = 'exitoso';
                return ['success' => true, 'data' => $responseData, 'status' => $response->status()];
            }

            $status = 'fallido';
            $message = $responseData['message'] ?? 'La API de Ingenia devolvió una respuesta fallida.';
            return ['success' => false, 'message' => $message, 'status' => $response->status(), 'data' => $responseData];

        } catch (Throwable $e) {
            $status = 'error_conexion';
            $statusCode = 500;

            if ($e instanceof RequestException) {
                $responseData = $e->response->json() ?? ['error_message' => $e->response->body()];
                $statusCode = $e->response->status();
            } else {
                $responseData = ['error' => $e->getMessage()];
            }

            Log::channel('ingenia_api')->error("Error en la petición a Ingenia [{$tipoConsulta}]", [
                'error' => $e->getMessage(),
                'response_body' => $responseData
            ]);

            return ['success' => false, 'message' => 'Error al comunicarse con el servicio de Ingenia.', 'status' => $statusCode, 'data' => $responseData];
        } finally {
            $curpFromResponse = $responseData['curp']['curp'] ?? null;
            $this->logApiCall($curpFromResponse, $tipoConsulta, $payload, $responseData, $status);
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
    private function mapEstado(string $estadoNombre): string
    {
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
            "Morelos" => "MS", "Nayarit" => "NT", 
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
        return $estados[$estadoNombre] ?? '';
    }
}

