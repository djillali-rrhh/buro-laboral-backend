<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BuroDeIngresos\BuroDeIngresosService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Buró de Ingresos API",
 *     version="1.0.0",
 *     description="API para gestión de consentimientos, verificaciones, información de perfil y webhooks del Buró de Ingresos",
 *     @OA\Contact(
 *         email="soporte@tuapp.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Servidor de desarrollo"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Ingresa tu token de autenticación"
 * )
 * 
 * @OA\Tag(
 *     name="Consentimientos",
 *     description="Gestión de consentimientos para acceso a información"
 * )
 * 
 * @OA\Tag(
 *     name="Verificaciones",
 *     description="Creación y gestión de verificaciones de ingresos"
 * )
 * 
 * @OA\Tag(
 *     name="Información",
 *     description="Consulta de perfil, empleos y facturas"
 * )
 * 
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Configuración y gestión de webhooks"
 * )
 */
class BuroDeIngresosController extends Controller
{
    use ApiResponse;

    /**
     * CONSENTIMIENTOS
     */

    /**
     * @OA\Post(
     *     path="/buro-ingresos/consents",
     *     summary="Crear un consentimiento",
     *     description="Crea un nuevo consentimiento para un CURP específico",
     *     tags={"Consentimientos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"curp", "privacy_notice_url"},
     *             @OA\Property(
     *                 property="curp",
     *                 type="string",
     *                 description="CURP del candidato (18 caracteres)",
     *                 example="ABCD850101HDFRRL09",
     *                 minLength=18,
     *                 maxLength=18
     *             ),
     *             @OA\Property(
     *                 property="privacy_notice_url",
     *                 type="string",
     *                 format="url",
     *                 description="URL del aviso de privacidad",
     *                 example="https://tuapp.com/aviso-privacidad"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consentimiento creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Consentimiento creado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="consent_id", type="string", example="consent_abc123"),
     *                 @OA\Property(property="curp", type="string", example="ABCD850101HDFRRL09"),
     *                 @OA\Property(property="status", type="string", example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The curp field must be 18 characters.")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createConsent(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'required|string|size:18',
            'privacy_notice_url' => 'required|url',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($validated['curp'])
                ->setIpAddress($request->ip())
                ->setPrivacyNoticeUrl($validated['privacy_notice_url']);

            $consent = $buroService->createConsent();

            if (!$consent['success']) {
                return $this->errorResponse(
                    $consent['message'] ?? 'Error al crear consentimiento',
                    $consent['http_code'] ?? 500
                );
            }

            return $this->successResponse($consent['data'], 'Consentimiento creado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear consentimiento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/buro-ingresos/consents/bulk",
     *     summary="Crear consentimientos en masa",
     *     description="Crea múltiples consentimientos para varios CURPs",
     *     tags={"Consentimientos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"curps", "privacy_notice_url"},
     *             @OA\Property(
     *                 property="curps",
     *                 type="array",
     *                 description="Array de CURPs (mínimo 1)",
     *                 @OA\Items(type="string", example="ABCD850101HDFRRL09")
     *             ),
     *             @OA\Property(
     *                 property="privacy_notice_url",
     *                 type="string",
     *                 format="url",
     *                 example="https://tuapp.com/aviso-privacidad"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consentimientos creados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Consentimientos creados exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="bulk_id", type="string", example="bulk_xyz789"),
     *                 @OA\Property(property="total", type="integer", example=5),
     *                 @OA\Property(property="created", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createBulkConsents(Request $request)
    {
        $validated = $request->validate([
            'curps' => 'required|array|min:1',
            'curps.*' => 'required|string|size:18',
            'privacy_notice_url' => 'required|url',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setIpAddress($request->ip())
                ->setPrivacyNoticeUrl($validated['privacy_notice_url']);

            $consents = $buroService->createBulkConsents($validated['curps']);

            if (!$consents['success']) {
                return $this->errorResponse(
                    $consents['message'] ?? 'Error al crear consentimientos',
                    $consents['http_code'] ?? 500
                );
            }

            return $this->successResponse($consents['data'], 'Consentimientos creados exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear consentimientos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/consents",
     *     summary="Listar consentimientos",
     *     description="Obtiene la lista de consentimientos con filtros opcionales",
     *     tags={"Consentimientos"},
     *     @OA\Parameter(
     *         name="curp",
     *         in="query",
     *         description="Filtrar por CURP específico",
     *         required=false,
     *         @OA\Schema(type="string", example="ABCD850101HDFRRL09")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="items_per_page",
     *         in="query",
     *         description="Elementos por página (máximo 100)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de consentimientos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Consentimientos obtenidos exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="consents",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="consent_id", type="string"),
     *                         @OA\Property(property="curp", type="string"),
     *                         @OA\Property(property="status", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=100),
     *                 @OA\Property(property="page", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function listConsents(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'sometimes|string|size:18',
            'page' => 'sometimes|integer|min:1',
            'items_per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $page = $validated['page'] ?? 1;
        $itemsPerPage = $validated['items_per_page'] ?? 100;

        try {
            $buroService = (new BuroDeIngresosService());

            if (isset($validated['curp'])) {
                $buroService->setCurp($validated['curp']);
            }

            $consents = $buroService->listConsents($page, $itemsPerPage);

            if (!$consents['success']) {
                return $this->errorResponse(
                    $consents['message'] ?? 'Error al listar consentimientos',
                    $consents['http_code'] ?? 500
                );
            }

            return $this->successResponse($consents['data'], 'Consentimientos obtenidos exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al listar consentimientos: ' . $e->getMessage(), 500);
        }
    }


    /**
     * VERIFICACIONES
     */

    /**
     * @OA\Post(
     *     path="/buro-ingresos/verifications",
     *     summary="Crear una verificación",
     *     description="Crea una nueva verificación para un CURP",
     *     tags={"Verificaciones"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"curp"},
     *             @OA\Property(
     *                 property="curp",
     *                 type="string",
     *                 description="CURP del candidato (18 caracteres)",
     *                 example="ABCD850101HDFRRL09"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificación creada exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="verification_id", type="string", example="ver_abc123"),
     *                 @OA\Property(property="curp", type="string", example="ABCD850101HDFRRL09"),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createVerification(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'required|string|size:18',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($validated['curp']);

            $verification = $buroService->createVerification();

            if (!$verification['success']) {
                return $this->errorResponse(
                    $verification['message'] ?? 'Error al crear verificación',
                    $verification['http_code'] ?? 500
                );
            }

            return $this->successResponse($verification['data'], 'Verificación creada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear verificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/verifications",
     *     summary="Listar verificaciones",
     *     description="Obtiene la lista de verificaciones con filtros opcionales",
     *     tags={"Verificaciones"},
     *     @OA\Parameter(
     *         name="curp",
     *         in="query",
     *         description="Filtrar por CURP",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="items_per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=100)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Fecha de inicio (formato Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificaciones obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificaciones obtenidas exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function listVerifications(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'sometimes|string|size:18',
            'page' => 'sometimes|integer|min:1',
            'items_per_page' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date_format:Y-m-d',
        ]);

        $page = $validated['page'] ?? 1;
        $itemsPerPage = $validated['items_per_page'] ?? 100;
        $startDate = $validated['start_date'] ?? null;

        try {
            $buroService = new BuroDeIngresosService();

            if (isset($validated['curp'])) {
                $buroService->setCurp($validated['curp']);
            }

            $verifications = $buroService->listVerifications($page, $itemsPerPage, $startDate);

            if (!$verifications['success']) {
                return $this->errorResponse(
                    $verifications['message'] ?? 'Error al listar verificaciones',
                    $verifications['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $verifications['data'],
                'Verificaciones obtenidas exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al listar verificaciones: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/buro-ingresos/verifications/bulk",
     *     summary="Crear verificaciones en masa",
     *     description="Crea múltiples verificaciones (máximo 100)",
     *     tags={"Verificaciones"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"verifications"},
     *             @OA\Property(
     *                 property="verifications",
     *                 type="array",
     *                 minItems=1,
     *                 maxItems=100,
     *                 @OA\Items(
     *                     @OA\Property(property="identifier", type="string", example="ABCD850101HDFRRL09"),
     *                     @OA\Property(property="external_id", type="string", example="EXT123")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificaciones creadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificaciones creadas exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createBulkVerifications(Request $request)
    {
        $validated = $request->validate([
            'verifications' => 'required|array|min:1|max:100',
            'verifications.*.identifier' => 'required|string|size:18',
            'verifications.*.external_id' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService();

            $verifications = $buroService->createBulkVerifications($validated['verifications']);

            if (!$verifications['success']) {
                return $this->errorResponse(
                    $verifications['message'] ?? 'Error al crear verificaciones',
                    $verifications['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $verifications['data'],
                'Verificaciones creadas exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al crear verificaciones: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/verifications/{verificationId}",
     *     summary="Obtener una verificación",
     *     description="Obtiene el estado de una verificación específica",
     *     tags={"Verificaciones"},
     *     @OA\Parameter(
     *         name="verificationId",
     *         in="path",
     *         description="ID de la verificación",
     *         required=true,
     *         @OA\Schema(type="string", example="ver_abc123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="verification_id", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="curp", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Verificación no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getVerification(string $verificationId)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setVerificationId($verificationId);

            $verification = $buroService->getVerification();

            return $this->successResponse($verification);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener verificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/verifications/bulk/{bulkId}",
     *     summary="Obtener estado de verificación en masa",
     *     description="Obtiene el estado de una verificación en masa",
     *     tags={"Verificaciones"},
     *     @OA\Parameter(
     *         name="bulkId",
     *         in="path",
     *         description="ID de la verificación en masa",
     *         required=true,
     *         @OA\Schema(type="string", example="bulk_xyz789")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Verificación en masa no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getBulkVerificationStatus(string $bulkId)
    {
        try {
            $buroService = new BuroDeIngresosService();

            $status = $buroService->getBulkVerificationStatus($bulkId);

            if (!$status['success']) {
                return $this->errorResponse(
                    $status['message'] ?? 'Error al obtener estado de verificación en masa',
                    $status['http_code'] ?? 500
                );
            }

            return $this->successResponse($status['data']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al obtener estado: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/buro-ingresos/verifications/{verificationId}",
     *     summary="Eliminar una verificación",
     *     description="Elimina una verificación específica",
     *     tags={"Verificaciones"},
     *     @OA\Parameter(
     *         name="verificationId",
     *         in="path",
     *         description="ID de la verificación",
     *         required=true,
     *         @OA\Schema(type="string", example="ver_abc123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificación eliminada exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Verificación no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function deleteVerification(string $verificationId)
    {
        try {
            $buroService = new BuroDeIngresosService();

            $result = $buroService->deleteVerification($verificationId);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Error al eliminar verificación',
                    $result['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $result['data'],
                'Verificación eliminada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar verificación: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/buro-ingresos/verifications/bulk/{bulkId}",
     *     summary="Eliminar verificación en masa",
     *     description="Elimina una verificación en masa y todas sus verificaciones asociadas",
     *     tags={"Verificaciones"},
     *     @OA\Parameter(
     *         name="bulkId",
     *         in="path",
     *         description="ID de la verificación en masa",
     *         required=true,
     *         @OA\Schema(type="string", example="bulk_xyz789")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación en masa eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Verificación en masa eliminada exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Verificación en masa no encontrada"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function deleteBulkVerification(string $bulkId)
    {
        try {
            $buroService = new BuroDeIngresosService();

            $result = $buroService->deleteBulkVerification($bulkId);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Error al eliminar verificación en masa',
                    $result['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $result['data'],
                'Verificación en masa eliminada exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar verificación en masa: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * INFORMACIÓN
     */

    /**
     * @OA\Get(
     *     path="/buro-ingresos/invoices/{identifier}",
     *     summary="Obtener facturas",
     *     description="Obtiene las facturas del candidato desde Buró de Ingresos",
     *     tags={"Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP del candidato",
     *         required=true,
     *         @OA\Schema(type="string", example="ABCD850101HDFRRL09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facturas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="invoices", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Candidato no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getInvoices(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $invoices = $buroService->getInvoices();

            if (!$invoices['success']) {
                return $this->errorResponse(
                    $invoices['message'] ?? 'Error al obtener invoices',
                    $invoices['http_code'] ?? 500
                );
            }

            return $this->successResponse($invoices['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/profile/{identifier}",
     *     summary="Obtener perfil",
     *     description="Obtiene el perfil del candidato desde Buró de Ingresos",
     *     tags={"Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP del candidato",
     *         required=true,
     *         @OA\Schema(type="string", example="ABCD850101HDFRRL09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Perfil obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="curp", type="string"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="phone", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Perfil no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getProfile(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $profile = $buroService->getProfile();

            if (!$profile['success']) {
                return $this->errorResponse(
                    $profile['message'] ?? 'Error al obtener el perfil',
                    $profile['http_code'] ?? 500
                );
            }

            return $this->successResponse($profile['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/employments/{identifier}",
     *     summary="Obtener historial de empleos",
     *     description="Obtiene el historial laboral del candidato",
     *     tags={"Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP del candidato",
     *         required=true,
     *         @OA\Schema(type="string", example="ABCD850101HDFRRL09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empleos obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="employments",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="company", type="string"),
     *                         @OA\Property(property="position", type="string"),
     *                         @OA\Property(property="start_date", type="string", format="date"),
     *                         @OA\Property(property="end_date", type="string", format="date")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Empleos no encontrados"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getEmployments(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $employments = $buroService->getEmployments();

            if (!$employments['success']) {
                return $this->errorResponse(
                    $employments['message'] ?? 'Error al obtener empleos',
                    $employments['http_code'] ?? 500
                );
            }

            return $this->successResponse($employments['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener empleos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/data/{identifier}",
     *     summary="Obtener datos completos del candidato",
     *     description="Obtiene perfil, empleos e invoices del candidato en una sola petición. Método auxiliar que no forma parte de la API original",
     *     tags={"Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP del candidato",
     *         required=true,
     *         @OA\Schema(type="string", example="ABCD850101HDFRRL09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos completos obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="profile", type="object"),
     *                 @OA\Property(property="employments", type="object"),
     *                 @OA\Property(property="invoices", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Candidato no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getCandidateData(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $profile = $buroService->getProfile();
            $employments = $buroService->getEmployments();
            $invoices = $buroService->getInvoices();

            return $this->successResponse([
                'profile' => $profile,
                'employments' => $employments,
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener datos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * WEBHOOKS
     */

    /**
     * @OA\Post(
     *     path="/buro-ingresos/webhooks",
     *     summary="Crear webhook",
     *     description="Crea un nuevo webhook para recibir notificaciones",
     *     tags={"Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"endpoint_url"},
     *             @OA\Property(
     *                 property="endpoint_url",
     *                 type="string",
     *                 format="url",
     *                 description="URL del endpoint que recibirá los webhooks",
     *                 example="https://tuapp.com/webhooks/buro"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Descripción del webhook (opcional)",
     *                 maxLength=255,
     *                 example="Webhook para notificaciones de verificaciones"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook creado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="webhook_id", type="string", example="webhook_123"),
     *                 @OA\Property(property="endpoint_url", type="string"),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createWebhook(Request $request)
    {
        $validated = $request->validate([
            'endpoint_url' => 'required|url',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService();

            $webhook = $buroService->createWebhook(
                $validated['endpoint_url'],
                $validated['description'] ?? null
            );

            if (!$webhook['success']) {
                return $this->errorResponse(
                    $webhook['message'] ?? 'Error al crear webhook',
                    $webhook['http_code'] ?? 500
                );
            }

            return $this->successResponse($webhook['data'], 'Webhook creado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear webhook: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/webhooks/{webhookId}",
     *     summary="Obtener webhook",
     *     description="Obtiene los detalles de un webhook específico",
     *     tags={"Webhooks"},
     *     @OA\Parameter(
     *         name="webhookId",
     *         in="path",
     *         description="ID del webhook",
     *         required=true,
     *         @OA\Schema(type="string", example="webhook_123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="webhook_id", type="string"),
     *                 @OA\Property(property="endpoint_url", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="description", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Webhook no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getWebhook(string $webhookId)
    {
        try {
            $buroService = new BuroDeIngresosService();

            $webhook = $buroService->getWebhook($webhookId);

            if (!$webhook['success']) {
                return $this->errorResponse(
                    $webhook['message'] ?? 'Error al obtener webhook',
                    $webhook['http_code'] ?? 500
                );
            }

            return $this->successResponse($webhook['data']);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener webhook: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/buro-ingresos/webhooks/{webhookId}",
     *     summary="Eliminar webhook",
     *     description="Elimina un webhook específico",
     *     tags={"Webhooks"},
     *     @OA\Parameter(
     *         name="webhookId",
     *         in="path",
     *         description="ID del webhook",
     *         required=true,
     *         @OA\Schema(type="string", example="webhook_123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook eliminado exitosamente")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Webhook no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function deleteWebhook(string $webhookId)
    {
        try {
            $buroService = new BuroDeIngresosService();

            $result = $buroService->deleteWebhook($webhookId);

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Error al eliminar webhook',
                    $result['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $result['data'],
                'Webhook eliminado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar webhook: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Patch(
     *     path="/buro-ingresos/webhooks/{webhookId}",
     *     summary="Actualizar webhook",
     *     description="Actualiza un webhook existente",
     *     tags={"Webhooks"},
     *     @OA\Parameter(
     *         name="webhookId",
     *         in="path",
     *         description="ID del webhook",
     *         required=true,
     *         @OA\Schema(type="string", example="webhook_123")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="endpoint_url",
     *                 type="string",
     *                 format="url",
     *                 description="Nueva URL del endpoint (opcional)",
     *                 example="https://tuapp.com/webhooks/buro-updated"
     *             ),
     *             @OA\Property(
     *                 property="is_active",
     *                 type="boolean",
     *                 description="Estado activo/inactivo (opcional)",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Nueva descripción (opcional)",
     *                 maxLength=255,
     *                 example="Webhook actualizado"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook actualizado exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="webhook_id", type="string"),
     *                 @OA\Property(property="endpoint_url", type="string"),
     *                 @OA\Property(property="is_active", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Webhook no encontrado"),
     *     @OA\Response(response=422, description="Error de validación"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function updateWebhook(Request $request, string $webhookId)
    {
        $validated = $request->validate([
            'endpoint_url' => 'sometimes|url',
            'is_active' => 'sometimes|boolean',
            'description' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService();

            $result = $buroService->updateWebhook(
                $webhookId,
                $validated['endpoint_url'] ?? null,
                $validated['is_active'] ?? null,
                $validated['description'] ?? null
            );

            if (!$result['success']) {
                return $this->errorResponse(
                    $result['message'] ?? 'Error al actualizar webhook',
                    $result['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $result['data'],
                'Webhook actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al actualizar webhook: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/webhooks",
     *     summary="Listar webhooks",
     *     description="Obtiene la lista de todos los webhooks registrados",
     *     tags={"Webhooks"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de webhooks obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="webhooks",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="webhook_id", type="string"),
     *                         @OA\Property(property="endpoint_url", type="string"),
     *                         @OA\Property(property="is_active", type="boolean"),
     *                         @OA\Property(property="description", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function listWebhooks()
    {
        try {
            $buroService = new BuroDeIngresosService();

            $webhooks = $buroService->listWebhooks();

            if (!$webhooks['success']) {
                return $this->errorResponse(
                    $webhooks['message'] ?? 'Error al listar webhooks',
                    $webhooks['http_code'] ?? 500
                );
            }

            return $this->successResponse($webhooks['data']);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al listar webhooks: ' . $e->getMessage(),
                500
            );
        }
    }
}
