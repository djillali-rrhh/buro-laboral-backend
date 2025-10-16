<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ObtenerNssRequest;
use App\Http\Requests\Api\V1\ObtenerTrayectoriaRequest;
use App\Services\APIMarket\ApiMarketService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ApiMarketController extends Controller
{
    use ApiResponse;

    /**
     * @var ApiMarketService
     */
    protected $apiMarketService;

    /**
     * ApiMarketController constructor.
     *
     * @param ApiMarketService $apiMarketService
     */
    public function __construct(ApiMarketService $apiMarketService)
    {
        $this->apiMarketService = $apiMarketService;
    }

    /**
     * Obtiene el NSS de un candidato a partir de su CURP.
     * La validación se delega a ObtenerNssRequest.
     *
     * @param ObtenerNssRequest $request
     * @return JsonResponse
     */
    public function obtenerNSS(ObtenerNssRequest $request): JsonResponse
    {
        $result = $this->apiMarketService->obtenerNSS($request->validated()['curp']);

        if (!$result['success']) {
            $statusCode = $result['status'] ?? 500;
            return $this->errorResponse($result['message'] ?? 'Error al consultar el servicio de NSS.', $statusCode);
        }

        $responseData = [
            'servicio' => 'obtener-nss',
            'resultado' => $result['data'],
        ];

        return $this->successResponse($responseData, 'NSS obtenido correctamente.');
    }

    /**
     * Obtiene la trayectoria laboral de un candidato a partir de su NSS o CURP.
     * La validación se delega a ObtenerTrayectoriaRequest.
     *
     * @param ObtenerTrayectoriaRequest $request
     * @return JsonResponse
     */
    public function obtenerTrayectoria(ObtenerTrayectoriaRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $identifier = $validatedData['nss'] ?? $validatedData['curp'];

        $result = $this->apiMarketService->obtenerTrayectoriaLaboral($identifier);

        if (!$result['success']) {
            $statusCode = $result['status'] ?? 500;
            return $this->errorResponse($result['message'] ?? 'Error al consultar la trayectoria laboral.', $statusCode);
        }

        $responseData = [
            'servicio' => 'consultar-historial-laboral-lite',
            'resultado' => $result['data'],
        ];

        return $this->successResponse($responseData, 'Trayectoria laboral obtenida correctamente.');
    }
}

