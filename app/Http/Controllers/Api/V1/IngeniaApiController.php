<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConsultaCurpRequest;
use App\Http\Requests\Api\V1\ObtenerCurpRequest;
use App\Services\IngeniaApiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Controlador para gestionar las interacciones con la API de Ingenia.
 *
 * Se encarga de recibir las peticiones HTTP, validarlas a través de Form Requests
 * y delegar la lógica de negocio al IngeniaApiService.
 *
 * @package App\Http\Controllers\Api\V1
 * @version 1.0.0
 */
class IngeniaApiController extends Controller
{
    use ApiResponse;

    /**
     * Crea una nueva instancia del controlador.
     *
     * Inyecta el servicio de Ingenia para desacoplar la lógica de negocio
     * del controlador.
     *
     * @param \App\Services\IngeniaApiService $ingeniaApiService El servicio que maneja la comunicación con la API de Ingenia.
     */
    public function __construct(private IngeniaApiService $ingeniaApiService)
    {
    }

    /**
     * Valida una CURP y consulta la información del candidato asociado.
     *
     * Utiliza ConsultaCurpRequest para la validación automática. Si la validación
     * es exitosa, delega la consulta al servicio y formatea la respuesta.
     *
     * @param  \App\Http\Requests\Api\V1\ConsultaCurpRequest  $request La petición validada que contiene la CURP.
     * @return \Illuminate\Http\JsonResponse Una respuesta JSON con los datos del candidato o un mensaje de error.
     */
    public function consultarPorCurp(ConsultaCurpRequest $request): JsonResponse
    {
        try {
            // La validación se ejecuta antes de que se llame a este método.
            $result = $this->ingeniaApiService->consultarPorCurp($request->validated()['curp']);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], $result['status'], $result['data'] ?? null);
            }

            return $this->successResponse($result['data'], 'Consulta por CURP realizada con éxito.');

        } catch (Throwable $e) {
            // Captura cualquier excepción inesperada durante el proceso.
            return $this->errorResponse('Error interno del servidor.', 500);
        }
    }

    /**
     * Valida datos personales y obtiene la CURP del candidato.
     *
     * Utiliza ObtenerCurpRequest para la validación automática. Si la validación
     * es exitosa, delega la obtención de la CURP al servicio y formatea la respuesta.
     *
     * @param  \App\Http\Requests\Api\V1\ObtenerCurpRequest  $request La petición validada con los datos personales.
     * @return \Illuminate\Http\JsonResponse Una respuesta JSON con la información de la CURP o un mensaje de error.
     */
    public function obtenerCurpPorDatos(ObtenerCurpRequest $request): JsonResponse
    {
        try {
            // La validación se ejecuta antes de que se llame a este método.
            $result = $this->ingeniaApiService->obtenerCurpPorDatos($request->validated());

            if (!$result['success']) {
                return $this->errorResponse($result['message'], $result['status'], $result['data'] ?? null);
            }

            return $this->successResponse($result['data'], 'Obtención de CURP por datos realizada con éxito.');

        } catch (Throwable $e) {
            // Captura cualquier excepción inesperada durante el proceso.
            return $this->errorResponse('Error interno del servidor.', 500);
        }
    }
}

