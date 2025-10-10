<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Traits\ApiResponse;
use App\Http\Requests\Api\V1\SearchByCurpRequest;
use App\Http\Requests\Api\V1\SearchByNameRequest;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class PoderJudicialController extends Controller
{
    use ApiResponse;

    /**
     * Inicia sesión en la API externa.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . '/acount');

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'status' => true,
                'message' => 'Login exitoso.',
                'data' => $data
            ]);
        }

        return $this->errorResponse('Error al iniciar sesión.', $response->status());
    }

    /**
     * Consulta un CURP en la API externa (GET).
     *
     * @param  string  $curp
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurp(string $curp)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . '/curp/' . $curp);

        if ($response->successful()) {
            return $this->successResponse($response->json(), 'CURP consultado exitosamente.');
        }

        return $this->errorResponse('Error al consultar el CURP.', $response->status());
    }

    /**
     * Búsqueda por nombres y apellidos.
     *
     * @param  string  $nombres
     * @param  string  $apellidos
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByNombresApellidos(string $nombres, string $apellidos)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . "/search/{$nombres}/{$apellidos}");

        if ($response->successful()) {
            return $this->successResponse($response->json(), 'Búsqueda por nombres y apellidos exitosa.');
        }

        return $this->errorResponse('Error en la búsqueda.', $response->status());
    }

    /**
     * Búsqueda por nombre completo.
     *
     * @param  string  $nombresCompleto
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByNombreCompleto(string $nombresCompleto)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . "/search/{$nombresCompleto}");

        if ($response->successful()) {
            return $this->successResponse($response->json(), 'Búsqueda por nombre completo exitosa.');
        }

        return $this->errorResponse('Error en la búsqueda.', $response->status());
    }

    /**
     * Búsqueda exacta por nombre completo.
     *
     * @param  string  $nombresCompleto
     * @return \Illuminate\Http\JsonResponse
     */
    public function exactSearchByNombreCompleto(string $nombresCompleto)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . "/exactsearch/{$nombresCompleto}");

        if ($response->successful()) {
            return $this->successResponse($response->json(), 'Búsqueda exacta por nombre completo exitosa.');
        }

        return $this->errorResponse('Error en la búsqueda exacta.', $response->status());
    }

    public function searchByCurpPost(SearchByCurpRequest $request)
        {
            try {
                $validatedData = $request->validated();

                $response = Http::withHeaders([
                    'apikey' => config('services.poder_judicial.secret'),
                ])->post(config('services.poder_judicial.base_uri') . '/curp', $validatedData);
                
                $response->throw(); 

                return $this->successResponse($response->json(), 'Búsqueda por CURP exitosa.');

            } catch (ConnectionException $e) {
                return $this->errorResponse('Error de conexión con el servicio externo.', 504);
            
            } catch (Throwable $e) {
                return $this->errorResponse(
                    'Error en el servicio externo: ' . $e->getMessage(),
                    $e->getCode()
                );
            }
        }
    public function searchByNamePost(SearchByNameRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->post(config('services.poder_judicial.base_uri') . '/search', $validatedData);
            
            $response->throw();

            return $this->successResponse($response->json(), 'Búsqueda por nombre (POST) exitosa.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error en el servicio externo: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Busca una empresa por nombre o razón social.
     *
     * @param  string  $nombreEmpresa
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCompanyByName(string $nombreEmpresa)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->get(config('services.poder_judicial.base_uri') . '/company-search/' . urlencode($nombreEmpresa));
            
            $response->throw();

            return $this->successResponse($response->json(), 'Búsqueda de empresa por nombre exitosa.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error en el servicio externo: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Busca una empresa por su RFC.
     *
     * @param  string  $claveRfc
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCompanyByRfc(string $claveRfc)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->get(config('services.poder_judicial.base_uri') . '/rfc/' . $claveRfc);
            
            $response->throw();

            return $this->successResponse($response->json(), 'Búsqueda de empresa por RFC exitosa.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error en el servicio externo: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Genera y obtiene la URL de un reporte en PDF a partir de un ID de búsqueda.
     *
     * @param  string  $searchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReportPdf(string $searchId)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->get(config('services.poder_judicial.base_uri') . '/report-pdf/' . $searchId);
            
            $response->throw();

            return $this->successResponse($response->json(), 'Reporte PDF generado exitosamente.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error al generar el reporte: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Consulta una cédula profesional por número.
     *
     * @param  string  $cedula
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCedulaByNumero(string $cedula)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->get(config('services.poder_judicial.base_uri') . '/cedula/' . $cedula);
            
            $response->throw();

            return $this->successResponse($response->json(), 'Cédula consultada exitosamente por número.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error al consultar la cédula: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Consulta cédulas profesionales por nombre completo.
     *
     * @param  string  $nombres
     * @param  string  $apellido1
     * @param  string  $apellido2
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCedulaByNombre(string $nombres, string $apellido1, string $apellido2)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => config('services.poder_judicial.secret'),
            ])->get(config('services.poder_judicial.base_uri') . "/cedula-nombre/{$nombres}/{$apellido1}/{$apellido2}");
            
            $response->throw();

            return $this->successResponse($response->json(), 'Cédulas consultadas exitosamente por nombre.');

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Error al consultar las cédulas: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }
}

