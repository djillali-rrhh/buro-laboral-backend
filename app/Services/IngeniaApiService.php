<?php

namespace App\Services;

use App\Models\RegistrosIngeniaApi;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para gestionar la comunicación con la API de Ingenia.
 *
 * Encapsula toda la lógica de negocio para las llamadas a la API externa,
 * incluyendo la construcción del payload, el manejo de errores y el
 * registro de auditoría para cada transacción.
 *
 * @package App\Services
 * @version 1.0.0
 */
class IngeniaApiService
{
    /**
     * La URL base para la API de Ingenia.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Crea una nueva instancia del servicio.
     *
     * Carga la URL base de la API desde el archivo de configuración de servicios.
     */
    public function __construct()
    {
        $this->baseUrl = config('services.ingenia.base_uri');
    }

    /**
     * Consulta la información de un candidato por su CURP.
     *
     * Realiza una petición POST al endpoint de consulta por CURP, maneja la respuesta
     * y registra la transacción.
     *
     * @param string $curp La CURP del candidato a consultar.
     * @return array Un arreglo con el resultado de la operación.
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
            
            // Lanza una excepción si la respuesta no fue exitosa (status code >= 300).
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
            return $this->handleException($e, $tipoConsulta, $status, $responseData);
        } finally {
            $this->logApiCall($curp, $tipoConsulta, $payload, $responseData, $status);
        }
    }

    /**
     * Obtiene el CURP de un candidato a partir de sus datos personales.
     *
     * Construye el payload con los datos personales, realiza la petición POST,
     * maneja la respuesta y registra la transacción.
     *
     * @param array $data Los datos personales validados del candidato.
     * @return array Un arreglo con el resultado de la operación.
     */
    public function obtenerCurpPorDatos(array $data): array
    {
        $payload = $data;
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
            return $this->handleException($e, $tipoConsulta, $status, $responseData);
        } finally {
            $curpFromResponse = $responseData['curp']['curp'] ?? null;
            $this->logApiCall($curpFromResponse, $tipoConsulta, $payload, $responseData, $status);
        }
    }

    /**
     * Registra la llamada a la API en la base de datos para auditoría.
     *
     * Este método se ejecuta siempre, sin importar el resultado de la petición.
     *
     * @param string|null $curp La CURP asociada a la consulta (si está disponible).
     * @param string $tipoConsulta El tipo de consulta realizada ('por_curp' o 'por_datos').
     * @param array $requestPayload El payload enviado a la API.
     * @param array|null $responsePayload La respuesta recibida de la API.
     * @param string $status El estado final de la transacción.
     * @return void
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
     * Centraliza el manejo de excepciones para las llamadas a la API.
     *
     * @param Throwable $e La excepción capturada.
     * @param string $tipoConsulta El tipo de consulta que falló.
     * @param string &$status La variable de estado que se actualizará.
     * @param array &$responseData El arreglo de respuesta que se llenará con los detalles del error.
     * @return array Un arreglo estandarizado de error.
     */
    private function handleException(Throwable $e, string $tipoConsulta, string &$status, array &$responseData): array
    {
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
    }
}

