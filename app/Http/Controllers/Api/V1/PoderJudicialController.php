<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Traits\ApiResponse;
use App\Http\Requests\Api\V1\SearchByCurpRequest;
use App\Http\Requests\Api\V1\SearchByNameRequest;
use Illuminate\Http\Client\ConnectionException;
use Throwable;
use Illuminate\Support\Facades\Log;
use App\Services\PoderJudicialService;

/**
 * Controlador para gestionar las interacciones con la API externa del Poder Judicial.
 *
 * @package App\Http\Controllers\Api\V1
 */
class PoderJudicialController extends Controller
{
    use ApiResponse;

    /**
     * Obtiene la información de la cuenta del servicio externo.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function account()
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->get(config('services.poder_judicial.base_uri') . '/acount');

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'status' => true,
                'message' => 'Verificación de cuenta exitosa.',
                'data' => $data
            ]);
        }

        return $this->errorResponse('Error al verificar la cuenta.', $response->status());
    }

    /**
     * Consulta la información de una persona a través de su CURP.
     *
     * @param string $curp La CURP a consultar.
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
     * Realiza una búsqueda por nombres y apellidos en el servicio externo.
     *
     * @param string $nombres Los nombres de la persona.
     * @param string $apellidos Los apellidos de la persona.
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
     * Realiza una búsqueda por nombre completo en el servicio externo.
     *
     * @param string $nombresCompleto El nombre completo de la persona.
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
     * Realiza una búsqueda exacta por nombre completo en el servicio externo.
     *
     * @param string $nombresCompleto El nombre completo exacto a buscar.
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

    /**
     * Realiza una búsqueda por CURP (POST), delega la lógica de API, persistencia y scoring al servicio.
     *
     * @param SearchByCurpRequest $request La solicitud validada con los datos de búsqueda.
     * @param PoderJudicialService $service El servicio que encapsula la lógica de negocio.
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByCurpPost(SearchByCurpRequest $request, PoderJudicialService $service)
    {
        try {
            $validatedData = $request->validated();
            
            $responseData = $service->searchAndPersistByCurp($validatedData);

            // Determina el éxito basado en si se encontraron datos de la CURP,
            // ignorando el 'status: false' de la API externa cuando solo no encuentra expedientes.
            $curpInfoFound = !empty($responseData['curp_info'] ?? $responseData['errors']['curp_info'] ?? []);

            if ($curpInfoFound) {
                $message = 'Búsqueda exitosa. Se encontraron datos de la CURP.';
                $totalResults = $responseData['total_results'] ?? $responseData['errors']['total_results'] ?? 0;
                
                if ($totalResults > 0) {
                    $message .= " Se encontraron {$totalResults} expedientes.";
                } else {
                    $message .= ' No se encontraron expedientes legales asociados.';
                }
                
                // Forzamos una respuesta exitosa ya que la consulta principal (CURP) fue correcta.
                return $this->successResponse($responseData, $message);
            }

            // Si ni siquiera se encontró la información de la CURP, es un fallo real.
            $fallbackMessage = 'La búsqueda no arrojó ningún resultado para la CURP proporcionada.';
            return $this->errorResponse($responseData['message'] ?? $fallbackMessage, 404, $responseData);

        } catch (ConnectionException $e) {
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 500);

        } catch (Throwable $e) {
            Log::error("Error en searchByCurpPost (General): " . $e->getMessage());
            return $this->errorResponse('Error interno del servidor al procesar la búsqueda.', $e->getCode() ?: 500);
        }
    }
    
    /**
     * Realiza una búsqueda por nombre (POST) y delega la persistencia al servicio.
     *
     * @param SearchByNameRequest $request La solicitud validada con los datos de búsqueda.
     * @param PoderJudicialService $service El servicio que encapsula la lógica de negocio.
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByNamePost(SearchByNameRequest $request, PoderJudicialService $service)
    {
        try {
            $validatedData = $request->validated();

            // Se asume que el servicio tiene un método análogo para buscar por nombre.
            // Este método debe ser creado en PoderJudicialService.
            $responseData = $service->searchAndPersistByName($validatedData);

            // La lógica de éxito es directa: si la llamada no lanzó una excepción, la búsqueda fue exitosa.
            // El mensaje simplemente indicará si se encontraron resultados o no.
            $totalResults = $responseData['total_results'] ?? $responseData['errors']['total_results'] ?? 0;
            
            if ($totalResults > 0) {
                $message = "Búsqueda por nombre exitosa. Se encontraron {$totalResults} expedientes.";
            } else {
                $message = 'Búsqueda por nombre exitosa. No se encontraron expedientes legales asociados.';
            }
            
            return $this->successResponse($responseData, $message);

        } catch (ConnectionException $e) {
            Log::error("Error de conexión en searchByNamePost: " . $e->getMessage());
            return $this->errorResponse('Error de conexión con el servicio externo.', 504);
        
        } catch (\RuntimeException $e) {
            // Captura errores específicos de persistencia que puedan originarse en el servicio.
            Log::error("Error de persistencia en searchByNamePost: " . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);

        } catch (Throwable $e) {
            Log::error("Error general en searchByNamePost: " . $e->getMessage());
            $code = is_int($e->getCode()) && $e->getCode() > 0 ? $e->getCode() : 500;
            return $this->errorResponse('Error interno del servidor al procesar la búsqueda por nombre.', $code);
        }
    }

    /**
     * Busca una empresa por su nombre en el servicio externo.
     *
     * @param string $nombreEmpresa El nombre de la empresa a buscar.
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
     * Busca una empresa por su RFC en el servicio externo.
     *
     * @param string $claveRfc El RFC de la empresa a buscar.
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
            )
            ;
        }
    }

    /**
     * Obtiene un reporte en formato PDF a partir de un ID de búsqueda.
     *
     * @param string $searchId El ID de la búsqueda previa.
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
     * Consulta una cédula profesional por su número.
     *
     * @param string $cedula El número de la cédula profesional.
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
     * Consulta cédulas profesionales por nombre completo de la persona.
     *
     * @param string $nombres Nombre(s) de la persona.
     * @param string $apellido1 Primer apellido.
     * @param string $apellido2 Segundo apellido.
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

