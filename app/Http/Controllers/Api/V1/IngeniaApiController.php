<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\IngeniaApiService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Support\Facades\Log;

class IngeniaApiController extends Controller
{
    use ApiResponse;

    protected $ingeniaApiService;

    public function __construct(IngeniaApiService $ingeniaApiService)
    {
        $this->ingeniaApiService = $ingeniaApiService;
    }

    /**
     * Consulta información por CURP.
     */
    public function consultarPorCurp(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'curp' => 'required|string|size:18',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Datos de entrada inválidos.', 422, $validator->errors());
            }

            $result = $this->ingeniaApiService->consultarPorCurp($request->input('curp'));

            if (!$result['success']) {
                return $this->errorResponse($result['message'], $result['status'], $result['data'] ?? null);
            }

            return $this->successResponse($result['data'], 'Consulta por CURP realizada con éxito.');

        } catch (Throwable $e) {
            Log::channel('ingenia_api')->error("Error no controlado en IngeniaApiController@consultarPorCurp: " . $e->getMessage());
            return $this->errorResponse('Error interno del servidor.', 500);
        }
    }

    /**
     * Obtiene CURP por datos personales.
     */
    public function obtenerCurpPorDatos(Request $request): JsonResponse
    {
        try {
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
            
            $result = $this->ingeniaApiService->obtenerCurpPorDatos($validator->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], $result['status'], $result['data'] ?? null);
            }

            return $this->successResponse($result['data'], 'Obtención de CURP por datos realizada con éxito.');

        } catch (Throwable $e) {
            Log::channel('ingenia_api')->error("Error no controlado en IngeniaApiController@obtenerCurpPorDatos: " . $e->getMessage());
            return $this->errorResponse('Error interno del servidor.', 500);
        }
    }
}

