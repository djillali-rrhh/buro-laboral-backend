<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BuroDeIngresos\BuroDeIngresosService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Buró de ingresos - Consentimientos",
 *     description="Gestión de consentimientos para acceso a información"
 * )
 * 
 * @OA\Tag(
 *     name="Buró de ingresos - Verificaciones",
 *     description="Creación y gestión de verificaciones de ingresos"
 * )
 * 
 * @OA\Tag(
 *     name="Buró de ingresos - Información",
 *     description="Consulta de perfil, empleos y facturas"
 * )
 * 
 * @OA\Tag(
 *     name="Buró de ingresos - Webhooks",
 *     description="Configuración y gestión de webhooks"
 * )
 * 
 * ============================================
 * SCHEMAS
 * ============================================
 * 
 * 
 * CONSENTIMIENTOS
 * 
 * @OA\Schema(
 *     schema="ConsentRequest",
 *     required={"curp", "privacy_notice_url"},
 *     @OA\Property(property="curp", type="string",
 *         description="CURP del candidato (18 caracteres)",
 *         example="GAWH790919MDFRRR02",
 *         minLength=18,
 *         maxLength=18
 *     ),
 *     @OA\Property(
 *         property="privacy_notice_url",
 *         type="string",
 *         format="url",
 *         description="URL del aviso de privacidad",
 *         example="https://tuapp.com/aviso-privacidad"
 *     )
 * )
 * 
 * @OA\Schema(
 *    schema="ConsentResponse",
 *     @OA\Property(property="id", type="string", example="0199de73-356c-7b56-a1ef-ca91b6bdb778"),
 *     @OA\Property(property="identifier", type="string", example="GAWH790919MDFRRR02"),
 *     @OA\Property(property="ip_address", type="string", example="127.0.0.1"),
 *     @OA\Property(property="privacy_notice_url", type="string", example="https://tuapp.com/aviso-privacidad"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-13T16:41:56.332707106Z"),
 *     @OA\Property(property="expires_at", type="string", format="date-time", example="2026-10-13T22:41:56.332412840Z")
 * )
 * 
 * @OA\Schema(
 *     schema="Consent",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="ID único del consentimiento",
 *         example="0199de73-356c-7b56-a1ef-ca91b6bdb778"
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP del candidato",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="ip_address",
 *         type="string",
 *         description="Dirección IP desde donde se creó el consentimiento",
 *         example="127.0.0.1"
 *     ),
 *     @OA\Property(
 *         property="privacy_notice_url",
 *         type="string",
 *         format="url",
 *         description="URL del aviso de privacidad aceptado",
 *         example="https://tuapp.com/aviso-privacidad"
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de expiración del consentimiento",
 *         example="2026-10-13T22:41:56.332413Z"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BulkConsentsRequest",
 *     required={"curps", "privacy_notice_url"},
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Array de CURPs",
 *         @OA\Items(type="string", example="GAWH790919MDFRRR02")
 *     ),
 *     @OA\Property(
 *         property="privacy_notice_url",
 *         type="string",
 *         format="url",
 *         example="https://tuapp.com/aviso-privacidad"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkConsentResultSuccess",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         example="01993595-0f06-7899-9df8-536e6f12fb77"
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         example="DNVQ190124HMNKDM46"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         example="successful"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-09-10T21:43:05.222529090Z"
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         example="2026-09-11T03:43:05.221725110Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkConsentResultFailed",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         nullable=true,
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         example="FAIL990101ABCDEFG1"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         example="failed"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         nullable=true,
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         nullable=true,
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="object",
 *         @OA\Property(property="code", type="string", example="validation_error"),
 *         @OA\Property(property="message", type="string", example="Invalid identifier format.")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkConsentsResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Consentimientos creados exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="Total de consentimientos procesados",
 *             example=3
 *         ),
 *         @OA\Property(
 *             property="successful",
 *             type="integer",
 *             description="Número de consentimientos exitosos",
 *             example=2
 *         ),
 *         @OA\Property(
 *             property="failed",
 *             type="integer",
 *             description="Número de consentimientos fallidos",
 *             example=1
 *         ),
 *         @OA\Property(
 *             property="results",
 *             type="array",
 *             description="Resultados individuales de cada consentimiento",
 *             @OA\Items(
 *                 oneOf={
 *                     @OA\Schema(ref="#/components/schemas/BulkConsentResultSuccess"),
 *                     @OA\Schema(ref="#/components/schemas/BulkConsentResultFailed")
 *                 }
 *             )
 *         )
 *     )
 * )
 *
 * 
 * @OA\Schema(
 *     schema="Pagination",
 *     @OA\Property(
 *         property="page",
 *         type="integer",
 *         description="Página actual",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="items_per_page",
 *         type="integer",
 *         description="Número de elementos por página",
 *         example=100
 *     ),
 *     @OA\Property(
 *         property="total_items",
 *         type="integer",
 *         description="Total de elementos",
 *         example=2
 *     ),
 *     @OA\Property(
 *         property="total_pages",
 *         type="integer",
 *         description="Total de páginas",
 *         example=1
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ListConsentsResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Consentimientos obtenidos exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="consents",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Consent")
 *         ),
 *         @OA\Property(
 *             property="pagination",
 *             ref="#/components/schemas/Pagination"
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="CreateVerificationRequest",
 *     required={"identifier"},
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC del candidato para iniciar la verificación",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         description="Identificador externo opcional para seguimiento. Puede usarse para correlacionar esta verificación con tus sistemas internos",
 *         example="EXT-2024-001",
 *         nullable=true
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="CreateVerificationResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificación creada exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="id",
 *             type="string",
 *             format="uuid",
 *             description="Identificador único del proceso de verificación",
 *             example="01993595-0f06-7899-9df8-536e6f12fb77"
 *         ),
 *         @OA\Property(
 *             property="identifier",
 *             type="string",
 *             description="CURP o RFC para el cual se inició la verificación",
 *             example="GAWH790919MDFRRR02"
 *         ),
 *         @OA\Property(
 *             property="status",
 *             type="string",
 *             enum={"in_progress", "completed", "failed"},
 *             description="Estado inicial de la verificación",
 *             example="in_progress"
 *         ),
 *         @OA\Property(
 *             property="consent_id",
 *             type="string",
 *             format="uuid",
 *             description="ID del consentimiento que fue encontrado y usado para esta verificación",
 *             example="0199de73-356c-7b56-a1ef-ca91b6bdb778"
 *         ),
 *         @OA\Property(
 *             property="created_at",
 *             type="string",
 *             format="date-time",
 *             description="Marca de tiempo de cuándo se creó la verificación",
 *             example="2025-09-10T21:43:05.222529090Z"
 *         ),
 *         @OA\Property(
 *             property="external_id",
 *             type="string",
 *             nullable=true,
 *             description="Identificador externo proporcionado en la petición, si existe",
 *             example="EXT-2024-001"
 *         )
 *     )
 * )
 * @OA\Schema(
 *     schema="Verification",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="ID único de la verificación",
 *         example="01993a02-ef11-7f21-8fcb-e1a2f908e2e3"
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC asociado a la verificación",
 *         example="CAOE931024T16"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"in_progress", "completed", "failed"},
 *         description="Estado actual de la verificación",
 *         example="completed"
 *     ),
 *     @OA\Property(
 *         property="consent_id",
 *         type="string",
 *         format="uuid",
 *         nullable=true,
 *         description="ID del consentimiento usado para esta verificación. Puede ser null si no se asoció ningún consentimiento",
 *         example="019939f7-5e75-799d-be63-253cf495ac36"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         nullable=true,
 *         description="Identificador externo opcional proporcionado durante la creación de la verificación",
 *         example="USER_001_VERIFICATION"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ListVerificationsResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificaciones obtenidas exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="verifications",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Verification")
 *         ),
 *         @OA\Property(
 *             property="pagination",
 *             ref="#/components/schemas/Pagination"
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkVerificationItem",
 *     required={"identifier"},
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC para el cual iniciar la verificación",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         description="Identificador externo opcional para seguimiento. Puede usarse para correlacionar esta verificación con tus sistemas internos",
 *         example="EXT-2024-001",
 *         nullable=true
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkVerificationsRequest",
 *     required={"verifications"},
 *     @OA\Property(
 *         property="verifications",
 *         type="array",
 *         description="Array de solicitudes de verificación con identificadores (CURP/RFC) y IDs externos opcionales",
 *         minItems=1,
 *         maxItems=100,
 *         @OA\Items(ref="#/components/schemas/BulkVerificationItem")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkVerificationResult",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="Identificador único de la verificación individual",
 *         example="01993a02-ef11-7f21-8fcb-e1a2f908e2e3"
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC para esta verificación",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"successful", "failed"},
 *         description="Estado de la creación de verificación individual",
 *         example="successful"
 *     ),
 *     @OA\Property(
 *         property="verification_status",
 *         type="string",
 *         enum={"in_progress", "completed", "failed"},
 *         description="Estado del procesamiento de la verificación",
 *         example="in_progress"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se creó esta verificación",
 *         example="2025-09-10T21:43:05.222529090Z"
 *     ),
 *     @OA\Property(
 *         property="consent_id",
 *         type="string",
 *         format="uuid",
 *         nullable=true,
 *         description="ID del consentimiento usado para esta verificación",
 *         example="019939f7-5e75-799d-be63-253cf495ac36"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         nullable=true,
 *         description="Identificador externo proporcionado en la solicitud, si existe",
 *         example="EXT-2024-001"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkVerificationsResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificaciones creadas exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="total",
 *             type="integer",
 *             description="Número total de solicitudes de verificación procesadas",
 *             example=3
 *         ),
 *         @OA\Property(
 *             property="successful",
 *             type="integer",
 *             description="Número de verificaciones creadas exitosamente",
 *             example=2
 *         ),
 *         @OA\Property(
 *             property="failed",
 *             type="integer",
 *             description="Número de creaciones de verificaciones fallidas",
 *             example=1
 *         ),
 *         @OA\Property(
 *             property="results",
 *             type="array",
 *             description="Array de resultados de verificaciones individuales",
 *             @OA\Items(ref="#/components/schemas/BulkVerificationResult")
 *         )
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="VerificationDetail",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="ID único de la verificación",
 *         example="01993a02-ef11-7f21-8fcb-e1a2f908e2e3"
 *     ),
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC asociado a la verificación",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"in_progress", "completed", "failed"},
 *         description="Estado actual de la verificación",
 *         example="completed"
 *     ),
 *     @OA\Property(
 *         property="data_available",
 *         type="boolean",
 *         description="Indica si se encontró algún dato para este identificador durante la verificación",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="can_retry",
 *         type="boolean",
 *         description="Indica si esta verificación puede reintentarse (ej. en caso de fallo sin datos)",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="entities",
 *         type="array",
 *         description="Lista de entidades de datos encontradas para este identificador (ej. 'profile', 'invoices', 'employment')",
 *         @OA\Items(type="string", example="profile"),
 *         example={"profile", "invoices", "employment"}
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se creó la verificación",
 *         example="2025-09-10T21:43:05.222529090Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Marca de tiempo de cuándo se actualizó por última vez la verificación (ej. completada o fallida)",
 *         example="2025-09-10T21:45:12.123456789Z"
 *     ),
 *     @OA\Property(
 *         property="consent_id",
 *         type="string",
 *         format="uuid",
 *         description="ID del consentimiento usado para esta verificación",
 *         example="019939f7-5e75-799d-be63-253cf495ac36"
 *     ),
 *     @OA\Property(
 *         property="is_billable",
 *         type="boolean",
 *         description="Indica si esta verificación es facturable",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         nullable=true,
 *         description="Identificador externo proporcionado en la solicitud, si existe",
 *         example="EXT-2024-001"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetVerificationResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificación obtenida exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/VerificationDetail"
 *     )
 * )
 * @OA\Schema(
 *     schema="BulkVerificationMetrics",
 *     @OA\Property(
 *         property="total",
 *         type="integer",
 *         description="Número total de verificaciones en el trabajo en masa",
 *         example=100
 *     ),
 *     @OA\Property(
 *         property="completed",
 *         type="integer",
 *         description="Número de verificaciones completadas",
 *         example=100
 *     ),
 *     @OA\Property(
 *         property="in_progress",
 *         type="integer",
 *         description="Número de verificaciones aún en progreso",
 *         example=0
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="BulkVerificationStatus",
 *     @OA\Property(
 *         property="metrics",
 *         ref="#/components/schemas/BulkVerificationMetrics"
 *     ),
 *     @OA\Property(
 *         property="bulk_id",
 *         type="string",
 *         format="uuid",
 *         description="Identificador único para este trabajo de verificación en masa",
 *         example="cd9441c8-6eab-4343-be54-329dc0cad139"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se creó la verificación en masa",
 *         example="2025-09-18T09:47:09.497623Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetBulkVerificationStatusResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Estado de verificación en masa obtenido exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/BulkVerificationStatus"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="DeleteVerificationResponse",
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="CURP o RFC asociado a la verificación eliminada",
 *         example="SAMPLE123456ABCDEF01"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"deleted"},
 *         description="Estado indicando que la verificación ha sido eliminada",
 *         example="deleted"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Mensaje de confirmación sobre la eliminación",
 *         example="Verification successfully deleted"
 *     ),
 *     @OA\Property(
 *         property="verification_id",
 *         type="string",
 *         format="uuid",
 *         description="ID de la verificación eliminada",
 *         example="01234567-89ab-cdef-0123-456789abcdef"
 *     ),
 *     @OA\Property(
 *         property="external_id",
 *         type="string",
 *         nullable=true,
 *         description="ID externo asociado a la verificación, si existe",
 *         example=null
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se eliminó la verificación",
 *         example="2025-09-29T17:40:53.745762917Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="DeleteVerificationSuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificación eliminada exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/DeleteVerificationResponse"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="DeleteBulkVerificationResponse",
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"deleted"},
 *         description="Estado indicando que la verificación en masa ha sido eliminada",
 *         example="deleted"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Mensaje de confirmación sobre la eliminación",
 *         example="Bulk verification and all associated verifications successfully deleted"
 *     ),
 *     @OA\Property(
 *         property="bulk_verification_id",
 *         type="string",
 *         format="uuid",
 *         description="ID de la verificación en masa eliminada",
 *         example="01234567-89ab-cdef-0123-456789abcdef"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se eliminó la verificación en masa",
 *         example="2025-09-29T18:10:47.716748205Z"
 *     ),
 *     @OA\Property(
 *         property="total_verifications",
 *         type="integer",
 *         description="Número total de verificaciones individuales que fueron eliminadas como parte de esta operación en masa",
 *         example=2
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="DeleteBulkVerificationSuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Verificación en masa eliminada exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/DeleteBulkVerificationResponse"
 *     )
 * )
 * @OA\Schema(
 *     schema="Address",
 *     @OA\Property(property="street", type="string", description="Calle", example="Av. Reforma 123"),
 *     @OA\Property(property="neighborhood", type="string", description="Colonia", example="Centro"),
 *     @OA\Property(property="municipality", type="string", description="Municipio", example="Cuauhtémoc"),
 *     @OA\Property(property="state", type="string", description="Estado", example="Ciudad de México"),
 *     @OA\Property(property="zip_code", type="string", description="Código postal", example="06000")
 * )
 * 
 * @OA\Schema(
 *     schema="PersonalInfo",
 *     @OA\Property(property="first_name", type="string", description="Nombre(s)", example="Juan"),
 *     @OA\Property(property="last_name", type="string", description="Apellidos", example="Pérez García"),
 *     @OA\Property(property="curp", type="string", description="CURP", example="GAWH790919MDFRRR02"),
 *     @OA\Property(property="nss", type="string", nullable=true, description="Número de Seguridad Social", example="12345678901"),
 *     @OA\Property(property="rfc", type="string", nullable=true, description="RFC", example="GAWH790919XXX"),
 *     @OA\Property(property="phone", type="string", nullable=true, description="Teléfono", example="+52 55 1234 5678"),
 *     @OA\Property(property="email", type="string", nullable=true, description="Email", example="juan.perez@email.com"),
 *     @OA\Property(property="address", ref="#/components/schemas/Address")
 * )
 * 
 * @OA\Schema(
 *     schema="ProfileData",
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="Identificador (CURP o RFC) asociado al perfil",
 *         example="GAWH790919MDFRRR02"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de la última actualización de datos para este perfil",
 *         example="2025-09-10T21:43:05.222529090Z"
 *     ),
 *     @OA\Property(
 *         property="personal_info",
 *         ref="#/components/schemas/PersonalInfo"
 *     ),
 *     @OA\Property(
 *         property="employment_status",
 *         type="string",
 *         enum={"employed", "unemployed", "unknown"},
 *         description="Estado laboral actual",
 *         example="employed"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetProfileResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Perfil obtenido exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/ProfileData"
 *     )
 * )
 * @OA\Schema(
 *     schema="InvoiceIncome",
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Monto del ingreso",
 *         example=3439.00
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="Moneda",
 *         example="MXN"
 *     ),
 *     @OA\Property(
 *         property="detail",
 *         type="string",
 *         description="Detalle del ingreso",
 *         example="Vales Despensa"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="InvoiceDeduction",
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Monto de la deducción",
 *         example=13152.76
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="Moneda",
 *         example="MXN"
 *     ),
 *     @OA\Property(
 *         property="detail",
 *         type="string",
 *         description="Detalle de la deducción",
 *         example="ISR Neto Articulo 96"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Invoice",
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"nomina", "ingreso", "egreso", "traslado", "pago"},
 *         description="Tipo de factura",
 *         example="nomina"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Monto total de la factura",
 *         example=54920.65
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="Moneda",
 *         example="MXN"
 *     ),
 *     @OA\Property(
 *         property="issue_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de emisión",
 *         example="2025-07-01"
 *     ),
 *     @OA\Property(
 *         property="rfc_issuer",
 *         type="string",
 *         description="RFC del emisor",
 *         example="NAN1105241B4"
 *     ),
 *     @OA\Property(
 *         property="issuer_name",
 *         type="string",
 *         description="Nombre del emisor",
 *         example="NANOMATERIALES"
 *     ),
 *     @OA\Property(
 *         property="rfc_receiver",
 *         type="string",
 *         description="RFC del receptor",
 *         example="CUAI911021Q11"
 *     ),
 *     @OA\Property(
 *         property="receiver_name",
 *         type="string",
 *         description="Nombre del receptor",
 *         example="ISIS MARIELA CRUZ AQUINO"
 *     ),
 *     @OA\Property(
 *         property="incomes",
 *         type="array",
 *         description="Lista de ingresos",
 *         @OA\Items(ref="#/components/schemas/InvoiceIncome")
 *     ),
 *     @OA\Property(
 *         property="deductions",
 *         type="array",
 *         description="Lista de deducciones",
 *         @OA\Items(ref="#/components/schemas/InvoiceDeduction")
 *     ),
 *     @OA\Property(
 *         property="folio_fiscal",
 *         type="string",
 *         description="Folio fiscal UUID",
 *         example="CD00309E-AA15-4C78-98C8-92EC4148092C"
 *     ),
 *     @OA\Property(
 *         property="invoice_status",
 *         type="string",
 *         description="Estado de la factura",
 *         example="Vigente"
 *     ),
 *     @OA\Property(
 *         property="zip_code_receiver",
 *         type="string",
 *         description="Código postal del receptor",
 *         example="01234"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="InvoicesData",
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="Identificador (CURP o RFC) asociado a las facturas",
 *         example="CUAI911021MOCRQS09"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de la última actualización de datos de las facturas",
 *         example="2025-09-10T09:01:05.329785Z"
 *     ),
 *     @OA\Property(
 *         property="invoices",
 *         type="array",
 *         description="Lista de facturas",
 *         @OA\Items(ref="#/components/schemas/Invoice")
 *     ),
 *     @OA\Property(
 *         property="pagination",
 *         ref="#/components/schemas/Pagination"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetInvoicesResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Facturas obtenidas exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/InvoicesData"
 *     )
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
     *     tags={"Buró de ingresos - Consentimientos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ConsentRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consentimiento creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/ConsentResponse")
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
     *     tags={"Buró de ingresos - Consentimientos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/BulkConsentsRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Consentimientos creados exitosamente",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/BulkConsentsResponse"   
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
     *     tags={"Buró de ingresos - Consentimientos"},
     *     @OA\Parameter(
     *         name="curp",
     *         in="query",
     *         description="Filtrar por CURP específico",
     *         required=false,
     *         @OA\Schema(type="string", example="GAWH790919MDFRRR02")
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
     *         @OA\JsonContent(ref="#/components/schemas/ListConsentsResponse")
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
     *     description="Inicia una verificación de forma asíncrona. Se enviará un webhook al completarse. La respuesta incluye el ID de verificación y estado inicial, junto con el consent_id que fue encontrado automáticamente",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateVerificationRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Proceso de verificación iniciado exitosamente. El proceso es asíncrono y se enviará un webhook al completarse",
     *         @OA\JsonContent(ref="#/components/schemas/CreateVerificationResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The identifier field is required.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createVerification(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'external_id' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($validated['identifier']);

            $verification = $buroService->createVerification($validated['external_id'] ?? null);

            if (!$verification['success']) {
                return $this->errorResponse(
                    $verification['message'] ?? 'Error al crear verificación',
                    $verification['http_code'] ?? 500
                );
            }

            // Retornar 202 Accepted en lugar de 200
            return response()->json([
                'success' => true,
                'message' => 'Verificación creada exitosamente',
                'data' => $verification['data']
            ], 202);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al crear verificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/verifications",
     *     summary="Listar verificaciones",
     *     description="Obtiene una lista paginada de verificaciones resumidas con filtros opcionales",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="query",
     *         description="Filtrar verificaciones por identificador (CURP o RFC)",
     *         required=false,
     *         @OA\Schema(type="string", example="CAOE931024T16")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página para paginación",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="items_per_page",
     *         in="query",
     *         description="Número de elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filtra verificaciones para incluir aquellas creadas en o después de esta fecha (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2024-01-01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de verificaciones resumidas",
     *         @OA\JsonContent(ref="#/components/schemas/ListVerificationsResponse")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function listVerifications(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'sometimes|string',
            'page' => 'sometimes|integer|min:1',
            'items_per_page' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date_format:Y-m-d',
        ]);

        $page = $validated['page'] ?? 1;
        $itemsPerPage = $validated['items_per_page'] ?? 100;
        $startDate = $validated['start_date'] ?? null;

        try {
            $buroService = new BuroDeIngresosService();

            if (isset($validated['identifier'])) {
                $buroService->setCurp($validated['identifier']);
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
     *     description="Crea múltiples verificaciones de forma asíncrona (máximo 100). Retorna un resumen de éxitos/fallos y detalles de verificaciones individuales",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Array de solicitudes de verificación con identificadores y IDs externos opcionales",
     *         @OA\JsonContent(ref="#/components/schemas/BulkVerificationsRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Solicitudes de verificación en masa procesadas. Retorna resumen de éxitos/fallos y detalles de verificaciones individuales",
     *         @OA\JsonContent(ref="#/components/schemas/BulkVerificationsResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The verifications field is required.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function createBulkVerifications(Request $request)
    {
        $validated = $request->validate([
            'verifications' => 'required|array|min:1|max:100',
            'verifications.*.identifier' => 'required|string',
            'verifications.*.external_id' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService();

            $verifications = $buroService->createBulkVerifications($validated['verifications']);


            Log::info('Bulk verifications created', [
                'verifications' => $validated['verifications'],
                'response' => $verifications
            ]);

            if (!$verifications['success']) {
                return $this->errorResponse(
                    $verifications['message'] ?? 'Error al crear verificaciones',
                    $verifications['http_code'] ?? 500
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Verificaciones creadas exitosamente',
                'data' => $verifications['data']
            ], 202);
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
     *     description="Obtiene información detallada del estado de una verificación específica",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\Parameter(
     *         name="verificationId",
     *         in="path",
     *         description="ID de la verificación a obtener",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="01993a02-ef11-7f21-8fcb-e1a2f908e2e3")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información detallada de la verificación",
     *         @OA\JsonContent(ref="#/components/schemas/GetVerificationResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Verificación no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Verificación no encontrada")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getVerification(string $verificationId)
    {
        try {
            $buroService = (new BuroDeIngresosService())
                ->setVerificationId($verificationId);

            $verification = $buroService->getVerification();

            return $this->successResponse(
                $verification,
                'Verificación obtenida exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener verificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/verifications/bulk/{bulkId}",
     *     summary="Obtener estado de verificación en masa",
     *     description="Obtiene el estado y métricas de un trabajo de verificación en masa",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\Parameter(
     *         name="bulkId",
     *         in="path",
     *         description="Identificador único del trabajo de verificación en masa",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="cd9441c8-6eab-4343-be54-329dc0cad139")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado de verificación en masa obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/GetBulkVerificationStatusResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Verificación en masa no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Verificación en masa no encontrada")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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

            return $this->successResponse(
                $status['data'],
                'Estado de verificación en masa obtenido exitosamente'
            );
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
     *     description="Elimina una verificación específica del sistema",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\Parameter(
     *         name="verificationId",
     *         in="path",
     *         description="ID de la verificación a eliminar",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="01234567-89ab-cdef-0123-456789abcdef")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación eliminada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/DeleteVerificationSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Verificación no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Verificación no encontrada")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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
     *     description="Elimina una verificación en masa y todas sus verificaciones individuales asociadas",
     *     tags={"Buró de ingresos - Verificaciones"},
     *     @OA\Parameter(
     *         name="bulkId",
     *         in="path",
     *         description="Identificador único del trabajo de verificación en masa a eliminar",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="01234567-89ab-cdef-0123-456789abcdef")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verificación en masa eliminada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/DeleteBulkVerificationSuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Verificación en masa no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Verificación en masa no encontrada")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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
     *     description="Obtiene las facturas del candidato desde Buró de Ingresos con filtros opcionales",
     *     tags={"Buró de ingresos - Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP o RFC del individuo/negocio",
     *         required=true,
     *         @OA\Schema(type="string", example="CUAI911021MOCRQS09")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar facturas por tipo. Si no se proporciona, se retornan todos los tipos",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"nomina", "ingreso", "egreso", "traslado", "pago"},
     *             example="nomina"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página para paginación",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="items_per_page",
     *         in="query",
     *         description="Número de elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=100, example=100)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filtra facturas emitidas en o después de esta fecha (YYYY-MM-DD). Se aplica a 'issue_date'",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filtra facturas emitidas en o antes de esta fecha (YYYY-MM-DD). Se aplica a 'issue_date'",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facturas del identificador",
     *         @OA\JsonContent(ref="#/components/schemas/GetInvoicesResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Facturas no encontradas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Facturas no encontradas")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getInvoices(Request $request, string $identifier)
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|in:nomina,ingreso,egreso,traslado,pago',
            'page' => 'sometimes|integer|min:1',
            'items_per_page' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $invoices = $buroService->getInvoices(
                $validated['type'] ?? null,
                $validated['page'] ?? 1,
                $validated['items_per_page'] ?? 100,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );

            if (!$invoices['success']) {
                return $this->errorResponse(
                    $invoices['message'] ?? 'Error al obtener invoices',
                    $invoices['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $invoices['data'],
                'Facturas obtenidas exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener invoices: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/profile/{identifier}",
     *     summary="Obtener perfil",
     *     description="Obtiene los datos del perfil del candidato desde Buró de Ingresos",
     *     tags={"Buró de ingresos - Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP o RFC del individuo/negocio",
     *         required=true,
     *         @OA\Schema(type="string", example="GAWH790919MDFRRR02")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos de perfil del identificador",
     *         @OA\JsonContent(ref="#/components/schemas/GetProfileResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Perfil no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Perfil no encontrado")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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

            return $this->successResponse(
                $profile['data'],
                'Perfil obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el perfil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/employments/{identifier}",
     *     summary="Obtener historial de empleos",
     *     description="Obtiene el historial laboral del candidato",
     *     tags={"Buró de ingresos - Información"},
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
     *     tags={"Buró de ingressos - Información"}
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
     *     tags={"Buró de ingresos - Webhooks"},
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
     *     tags={"Buró de ingresos - Webhooks"},
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
     *     tags={"Buró de ingresos - Webhooks"},
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
     *     tags={"Buró de ingresos - Webhooks"},
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
     *     tags={"Buró de ingresos - Webhooks"},
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
