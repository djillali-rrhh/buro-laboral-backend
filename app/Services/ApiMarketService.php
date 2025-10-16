<?php

namespace App\Services;

use App\Models\RegistrosApiMarket;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para gestionar la comunicación con la API de ApiMarket.
 *
 * Encapsula la lógica para obtener el NSS y la trayectoria laboral,
 * incluyendo el manejo de errores y el registro de auditoría para cada transacción.
 *
 * @package App\Services
 * @version 1.0.3
 */
class ApiMarketService
{
    /**
     * @var string La URL base para la API de ApiMarket.
     */
    protected string $baseUrl;

    /**
     * @var string El token de autenticación para la API.
     */
    protected string $token;

    /**
     * @var bool Define si el servicio debe operar en modo sandbox.
     */
    protected bool $isSandbox;

    /**
     * Crea una nueva instancia del servicio.
     */
    public function __construct()
    {
        $this->baseUrl = config('services.apimarket.api_url');
        $this->token = config('services.apimarket.token');
        $this->isSandbox = config('services.apimarket.sandbox', false);
    }

    /**
     * Obtiene el NSS de un candidato a partir de su CURP.
     *
     * @param string $curp La CURP del candidato.
     * @return array El resultado de la operación.
     */
    public function obtenerNSS(string $curp): array
    {
        $servicio = 'obtener-nss';
        $endpoint = 'api/imss/grupo/localizar-nss';
        $payload = ['curp' => $curp];
        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('apimarket_api')->info("Iniciando consulta [{$servicio}]", $payload);

            $request = Http::withToken($this->token)->withoutVerifying();
            if ($this->isSandbox) {
                $request->withHeaders(['x-sandbox' => 'true']);
            }

            $response = $request->post($this->baseUrl . $endpoint, $payload);
            $response->throw();

            $responseData = $response->json() ?? [];

            if ($response->successful()) {
                $status = !empty($responseData['nss']) ? 'exitoso' : 'exitoso_sin_datos';
                return ['success' => true, 'data' => $responseData, 'status' => $response->status()];
            }

            // Este bloque es redundante si se usa throw(), pero se mantiene por seguridad.
            $status = 'fallido';
            $message = $responseData['message'] ?? 'Respuesta no exitosa de ApiMarket.';
            return ['success' => false, 'message' => $message, 'status' => $response->status(), 'data' => $responseData];

        } catch (Throwable $e) {
            return $this->handleException($e, $servicio, $status, $responseData);
        } finally {
            $this->registrarAuditoria($servicio, $payload, $responseData, $status);
        }
    }

    /**
     * Obtiene la trayectoria laboral a partir de un NSS o CURP.
     *
     * @param string $identifier El NSS o CURP del candidato.
     * @return array El resultado de la operación.
     */
    public function obtenerTrayectoriaLaboral(string $identifier): array
    {
        $servicio = 'consultar-historial-laboral-lite';
        $endpoint = 'api/imss/grupo/trayectoria-laboral';
        $payload = ['identificador' => $identifier];
        $status = 'iniciado';
        $responseData = [];

        try {
            Log::channel('apimarket_api')->info("Iniciando consulta [{$servicio}]", $payload);

            $request = Http::withToken($this->token)->withoutVerifying();
            if ($this->isSandbox) {
                $request->withHeaders(['x-sandbox' => 'true']);
            }

            // CORRECCIÓN: Se cambió de ->get() a ->post() para resolver el error 405 Method Not Allowed.
            $response = $request->post($this->baseUrl . $endpoint, $payload);
            $response->throw();

            $responseData = $response->json() ?? [];

            if ($response->successful()) {
                $status = !empty($responseData) ? 'exitoso' : 'exitoso_sin_datos';
                return ['success' => true, 'data' => $responseData, 'status' => $response->status()];
            }

            // Redundante con throw(), pero se mantiene por seguridad.
            $status = 'fallido';
            $message = $responseData['message'] ?? 'Respuesta no exitosa de ApiMarket.';
            return ['success' => false, 'message' => $message, 'status' => $response->status(), 'data' => $responseData];

        } catch (Throwable $e) {
            return $this->handleException($e, $servicio, $status, $responseData);
        } finally {
            $this->registrarAuditoria($servicio, $payload, $responseData, $status);
        }
    }

    /**
     * Registra la llamada a la API en la base de datos para auditoría.
     *
     * @param string $servicio El nombre del servicio invocado.
     * @param array $requestPayload El payload enviado a la API.
     * @param array|null $responsePayload La respuesta recibida de la API.
     * @param string $status El estado final de la transacción.
     */
    private function registrarAuditoria(string $servicio, array $requestPayload, ?array $responsePayload, string $status): void
    {
        $curp = null;
        $nss = null;

        $identifier = $requestPayload['curp'] ?? $requestPayload['identificador'] ?? null;

        if ($identifier) {
            // Verifica si el identificador parece ser un NSS (11 dígitos numéricos)
            if (strlen($identifier) === 11 && is_numeric($identifier)) {
                $nss = $identifier;
                // Intenta obtener la CURP desde la respuesta de la API de trayectoria
                $curp = $responsePayload['data']['data']['curp'] ?? null;
            } else {
                // Asume que es una CURP
                $curp = $identifier;
                // Intenta obtener el NSS desde la respuesta de la API de NSS o de trayectoria
                $nss = $responsePayload['nss'] ?? ($responsePayload['data']['data']['nss'] ?? null);
            }
        }

        try {
            RegistrosApiMarket::create([
                'servicio' => $servicio,
                'curp' => $curp,
                'nss' => $nss,
                'payload_request' => json_encode($requestPayload),
                'payload_response' => json_encode($responsePayload),
                'estatus' => $status,
            ]);
        } catch (Throwable $e) {
            Log::channel('apimarket_api')->critical("¡FALLO AL GUARDAR LOG DE AUDITORÍA DE APIMARKET!", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Centraliza el manejo de excepciones para las llamadas a la API.
     *
     * @param Throwable $e La excepción capturada.
     * @param string $servicio El servicio que falló.
     * @param string &$status La variable de estado que se actualizará.
     * @param array &$responseData El arreglo de respuesta.
     * @return array Un arreglo estandarizado de error.
     */
    private function handleException(Throwable $e, string $servicio, string &$status, array &$responseData): array
    {
        $status = 'error_conexion';
        $statusCode = 500;
        
        if ($e instanceof RequestException) {
            $responseData = $e->response->json() ?? ['error_message' => $e->response->body()];
            $statusCode = $e->response->status();
        } else {
            $responseData = ['error' => $e->getMessage()];
        }

        Log::channel('apimarket_api')->error("Error en la petición a ApiMarket [{$servicio}]", [
            'error' => $e->getMessage(),
            'response_body' => $responseData
        ]);

        return ['success' => false, 'message' => 'Error al comunicarse con el servicio de ApiMarket.', 'status' => $statusCode, 'data' => $responseData];
    }
}

