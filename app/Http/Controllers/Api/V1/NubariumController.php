<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Nubarium\NubariumService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\ObtenerCurpRequest;

/**
 * Nubarium API
 *
 * APIs para obtener y validar CURP a través de Nubarium.
 */
class NubariumController extends Controller
{
    use ApiResponse;

    private NubariumService $service;

    public function __construct(NubariumService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/nubarium/curp
     * 
     * Obtiene un CURP basado en los datos personales enviados.
     *
     * Payload de ejemplo:
     * {
     *   "nombre": "NOMBRE_EJEMPLO",
     *   "primer_apellido": "APELLIDO_EJEMPLO",
     *   "segundo_apellido": "APELLIDO2_EJEMPLO",
     *   "fecha_nacimiento": "1990-01-01",
     *   "entidad": "ENTIDAD_EJEMPLO",
     *   "sexo": "H"
     * }
     *
     * Respuesta de ejemplo:
     * {
     *   "status": true,
     *   "message": "CURP obtenida exitosamente",
     *   "data": {
     *       "message": "CURP obtenida exitosamente",
     *       "data": {
     *           "apellidoMaterno": "APELLIDO_EJEMPLO",
     *           "apellidoPaterno": "APELLIDO2_EJEMPLO",
     *           "curp": "CURP123456HDFXYZ00",
     *           "fechaNacimiento": "01/01/1990",
     *           "nombre": "NOMBRE_EJEMPLO",
     *           "sexo": "HOMBRE",
     *           "estadoNacimiento": "ENTIDAD_EJEMPLO",
     *           "paisNacimiento": "MEXICO",
     *           "datosDocProbatorio": { ... }
     *       }
     *   }
     * }
     */
    public function obtenerCurp(ObtenerCurpRequest $request)
    {
        $validated = $request->validated();

        $payload = [
            "nombre" => $validated['nombre'],
            "primer_apellido" => $validated['paterno'],
            "segundo_apellido" => $validated['materno'] ?? null,
            "fecha_nacimiento" => sprintf(
                "%02d/%02d/%04d",
                (int)$validated['dia'],
                (int)$validated['mes'],
                (int)$validated['anio']
            ),
            "entidad" => $validated['estado'],
            "sexo" => $validated['sexo'],
        ];

        $response = $this->service->obtenerCurpPorDatos($payload);

        return $this->successResponse([$response]);
    }

    /**
     * POST /api/v1/nubarium/validar
     * 
     * Valida un CURP y devuelve información asociada.
     *
     * Payload de ejemplo:
     * {
     *   "curp": "CURP123456HDFXYZ00"
     * }
     *
     * Respuesta de ejemplo:
     * {
     *   "status": true,
     *   "message": "CURP válida",
     *   "data": {
     *       "message": "CURP válida",
     *       "data": {
     *           "apellidoMaterno": "APELLIDO_EJEMPLO",
     *           "apellidoPaterno": "APELLIDO2_EJEMPLO",
     *           "curp": "CURP123456HDFXYZ00",
     *           "fechaNacimiento": "01/01/1990",
     *           "nombre": "NOMBRE_EJEMPLO",
     *           "sexo": "HOMBRE",
     *           "estadoNacimiento": "ENTIDAD_EJEMPLO",
     *           "paisNacimiento": "MEXICO",
     *           "datosDocProbatorio": { ... }
     *       }
     *   }
     * }
     */
    public function validarCurp(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'required|string|size:18',
        ]);

        $data = $this->service->validarCurp($validated['curp']);

        return $this->successResponse($data);
    }
}
