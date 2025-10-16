<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiMarketService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiMarketController extends Controller
{
    // Usa el trait para tener acceso a los métodos successResponse y errorResponse.
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function obtenerNSS(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'curp' => ['required', 'string', 'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Los datos proporcionados son inválidos.', 422, $validator->errors()->toArray());
        }

        $result = $this->apiMarketService->obtenerNSS($request->curp);

        if (!$result['success']) {
            // Se obtiene el código de estado del servicio, o se usa 500 por defecto.
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function obtenerTrayectoria(Request $request): JsonResponse
    {
        $identifier = $request->input('nss', $request->input('curp'));

        if (!$identifier) {
            return $this->errorResponse('Se requiere un NSS o CURP.', 422);
        }

        $result = $this->apiMarketService->obtenerTrayectoriaLaboral($identifier);

        if (!$result['success']) {
            // Se obtiene el código de estado del servicio, o se usa 500 por defecto.
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

