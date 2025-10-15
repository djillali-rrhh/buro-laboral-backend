<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BuroDeIngresos\BuroDeIngresosService;
use App\Services\BuroDeIngresos\BuroDeIngresosWebhookService;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Services\BuroDeIngresos\Actions\{
    ProcessCandidatoDatos,
    ProcessCandidatoDatosExtra,
    ProcessCandidatoLaborales,
    ProcessDocumentosSA,
};

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
 * 
 * @OA\Schema(
 *     schema="EmploymentRecord",
 *     @OA\Property(
 *         property="employer",
 *         type="string",
 *         description="Nombre del empleador",
 *         example="SECRETARIA DE SALUD"
 *     ),
 *     @OA\Property(
 *         property="employer_registration",
 *         type="string",
 *         nullable=true,
 *         description="Número de registro del empleador. Puede ser null para ciertas instituciones como ISSSTE",
 *         example="Y522272310"
 *     ),
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date",
 *         description="Fecha de inicio del empleo",
 *         example="2023-01-15"
 *     ),
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date",
 *         nullable=true,
 *         description="Fecha de fin del empleo. Null si actualmente está empleado",
 *         example="2022-12-31"
 *     ),
 *     @OA\Property(
 *         property="federal_entity",
 *         type="string",
 *         nullable=true,
 *         description="Entidad federativa (ej. estado) donde está registrado el empleo",
 *         example="CIUDAD DE MEXICO"
 *     ),
 *     @OA\Property(
 *         property="base_salary",
 *         type="number",
 *         format="float",
 *         description="Salario base del empleo",
 *         example=1150.5
 *     ),
 *     @OA\Property(
 *         property="monthly_salary",
 *         type="number",
 *         format="float",
 *         description="Salario mensual calculado",
 *         example=34990.25
 *     ),
 *     @OA\Property(
 *         property="pdf_link",
 *         type="string",
 *         format="uri",
 *         description="Enlace al documento PDF de este registro de empleo",
 *         example="https://storage.googleapis.com/income-bureau-files-stg/issste/CUAI911021MOCRQS09.pdf"
 *     ),
 *     @OA\Property(
 *         property="institution",
 *         type="string",
 *         enum={"imss", "issste"},
 *         description="Institución con la que está asociado el empleo",
 *         example="issste"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="EmploymentsData",
 *     @OA\Property(
 *         property="identifier",
 *         type="string",
 *         description="Identificador (CURP) asociado al historial laboral",
 *         example="CUAI911021MOCRQS09"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de la última actualización de datos para este historial laboral",
 *         example="2025-09-10T17:40:22.153499Z"
 *     ),
 *     @OA\Property(
 *         property="semanas_cotizadas",
 *         type="integer",
 *         description="Número total de semanas cotizadas",
 *         example=250
 *     ),
 *     @OA\Property(
 *         property="employment_history",
 *         type="array",
 *         description="Historial de empleos",
 *         @OA\Items(ref="#/components/schemas/EmploymentRecord")
 *     ),
 *     @OA\Property(
 *         property="pagination",
 *         ref="#/components/schemas/Pagination"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetEmploymentsResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Historial laboral obtenido exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/EmploymentsData"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="CandidateCompleteData",
 *     @OA\Property(
 *         property="profile",
 *         ref="#/components/schemas/ProfileData",
 *         description="Información del perfil del candidato"
 *     ),
 *     @OA\Property(
 *         property="employments",
 *         ref="#/components/schemas/EmploymentsData",
 *         description="Historial laboral completo del candidato"
 *     ),
 *     @OA\Property(
 *         property="invoices",
 *         ref="#/components/schemas/InvoicesData",
 *         description="Facturas del candidato"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetCandidateDataResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Datos del candidato obtenidos exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/CandidateCompleteData"
 *     )
 * )
 * 
 * 
 * @OA\Schema(
 *     schema="CreateWebhookRequest",
 *     required={"endpoint_url"},
 *     @OA\Property(
 *         property="endpoint_url",
 *         type="string",
 *         format="uri",
 *         description="URL para enviar las notificaciones del webhook",
 *         example="https://tu-app.com/api/webhooks/buro-ingresos"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descripción del webhook",
 *         example="Webhook para notificaciones de verificación"
 *     ),
 * )
 * 
 * @OA\Schema(
 *     schema="WebhookData",
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         description="Identificador único del webhook",
 *         example="0199de73-356c-7b56-a1ef-ca91b6bdb778"
 *     ),
 *     @OA\Property(
 *         property="company_id",
 *         type="string",
 *         format="uuid",
 *         description="Identificador de la empresa propietaria del webhook",
 *         example="0199de73-356c-7b56-a1ef-ca91b6bdb779"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descripción del webhook",
 *         example="Webhook para notificaciones de verificación"
 *     ),
 *     @OA\Property(
 *         property="endpoint_url",
 *         type="string",
 *         format="uri",
 *         description="URL para enviar las notificaciones del webhook",
 *         example="https://tu-app.com/api/webhooks/buro-ingresos"
 *     ),
 *     @OA\Property(
 *         property="secret_key",
 *         type="string",
 *         description="Clave secreta usada para verificar la firma en el header X-Signature del webhook, asegurando que la petición proviene del Buró de Ingresos",
 *         example="wh_sec_abc123def456ghi789"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Indica si el webhook está activo",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de cuándo se creó el webhook",
 *         example="2025-10-13T16:41:56.332707106Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Marca de tiempo de la última actualización del webhook",
 *         example="2025-10-13T16:41:56.332707106Z"
 *     ),
 * )
 * 
 * @OA\Schema(
 *     schema="CreateWebhookResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Webhook creado exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/WebhookData"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="GetWebhookResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Webhook obtenido exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/WebhookData"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="DeleteWebhookResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Webhook eliminado exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         nullable=true,
 *         description="Datos adicionales de la eliminación (puede ser null)",
 *         example=null
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ListWebhooksResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Webhooks obtenidos exitosamente"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         description="Lista de webhooks registrados",
 *         @OA\Items(ref="#/components/schemas/WebhookData")
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="WebhookEvent",
 *     type="object",
 *     title="Evento de Webhook",
 *     description="Estructura estándar de un evento enviado por Buró de Ingresos vía webhook.",
 *     required={"event", "verification_id", "identifier", "status", "timestamp"},
 *     @OA\Property(property="event", type="string", example="verification.completed", description="Tipo de evento emitido."),
 *     @OA\Property(property="verification_id", type="string", format="uuid", example="bd830637-ea6e-4888-80ab-a09f01fc9209", description="Identificador único de la verificación."),
 *     @OA\Property(property="identifier", type="string", example="OICE940722HGFRST08", description="Identificador del candidato (CURP o RFC)."),
 *     @OA\Property(property="status", type="string", enum={"in_progress","completed"}, example="completed", description="Estado actual de la verificación."),
 *     @OA\Property(property="data_available", type="boolean", example=true, description="Indica si hay datos disponibles para consulta."),
 *     @OA\Property(property="can_retry", type="boolean", example=false, description="Indica si el proceso puede volver a intentarse."),
 *     @OA\Property(
 *         property="entities",
 *         type="array",
 *         description="Lista de entidades incluidas en la verificación completada.",
 *         @OA\Items(type="string", enum={"profile","employment","invoices"})
 *     ),
 *     @OA\Property(property="last_updated_at", type="string", format="date-time", example="2025-04-29T10:37:25Z", description="Última fecha de actualización de la verificación."),
 *     @OA\Property(property="timestamp", type="string", format="date-time", example="2025-04-29T10:37:25Z", description="Fecha y hora exacta en que se envió el evento."),
 *     @OA\Property(property="external_id", type="string", nullable=true, example=null, description="Identificador externo definido por el cliente.")
 * )
 */

class BuroDeIngresosController extends Controller
{
    public function __construct(
        private readonly BuroDeIngresosWebhookService $webhookService
    ) {}

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
     *     summary="Obtener historial laboral",
     *     description="Obtiene el historial laboral del candidato desde Buró de Ingresos, incluyendo semanas cotizadas y empleos en IMSS/ISSSTE",
     *     tags={"Buró de ingresos - Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP del individuo",
     *         required=true,
     *         @OA\Schema(type="string", example="CUAI911021MOCRQS09")
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
     *         description="Opcional. Filtra registros de empleo que se superponen o comienzan en/después de esta fecha (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2020-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Opcional. Filtra registros de empleo que se superponen o terminan en/antes de esta fecha (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial laboral del identificador (CURP)",
     *         @OA\JsonContent(ref="#/components/schemas/GetEmploymentsResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Historial laboral no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Historial laboral no encontrado")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getEmployments(Request $request, string $identifier)
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'items_per_page' => 'sometimes|integer|min:1|max:100',
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|date_format:Y-m-d',
        ]);

        try {
            $buroService = (new BuroDeIngresosService())
                ->setCurp($identifier);

            $employments = $buroService->getEmployments(
                $validated['page'] ?? 1,
                $validated['items_per_page'] ?? 100,
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null
            );

            if (!$employments['success']) {
                return $this->errorResponse(
                    $employments['message'] ?? 'Error al obtener empleos',
                    $employments['http_code'] ?? 500
                );
            }

            return $this->successResponse(
                $employments['data'],
                'Historial laboral obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener empleos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/buro-ingresos/data/{identifier}",
     *     summary="Obtener datos completos del candidato",
     *     description="Obtiene toda la información disponible del candidato desde Buró de Ingresos: perfil, historial laboral y facturas en una sola petición",
     *     tags={"Buró de ingresos - Información"},
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         description="CURP o RFC del individuo/negocio",
     *         required=true,
     *         @OA\Schema(type="string", example="CUAI911021MOCRQS09")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos completos del candidato obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/GetCandidateDataResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Datos del candidato no encontrados",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Datos del candidato no encontrados")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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
                'profile' => $profile['data'] ?? null,
                'employments' => $employments['data'] ?? null,
                'invoices' => $invoices['data'] ?? null,
            ], 'Datos del candidato obtenidos exitosamente');
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
     *     summary="Crear un webhook",
     *     description="Registra una nueva URL de webhook para recibir notificaciones de eventos de verificación",
     *     tags={"Buró de ingresos - Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Configuración del webhook a crear",
     *         @OA\JsonContent(ref="#/components/schemas/CreateWebhookRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Webhook creado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/CreateWebhookResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The endpoint_url field is required.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
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
     *     summary="Obtener un webhook",
     *     description="Obtiene los detalles de un webhook específico por su ID",
     *     tags={"Buró de ingresos - Webhooks"},
     *     @OA\Parameter(
     *         name="webhookId",
     *         in="path",
     *         description="ID del webhook a obtener",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del webhook obtenidos exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/GetWebhookResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Webhook no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado - API key inválida o faltante",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autenticado")
     *         )
     *     ),
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

            return $this->successResponse(
                $webhook['data'],
                'Webhook obtenido exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener webhook: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/buro-ingresos/webhooks/{webhookId}",
     *     summary="Eliminar un webhook",
     *     description="Elimina un webhook específico por su ID",
     *     tags={"Buró de ingresos - Webhooks"},
     *     @OA\Parameter(
     *         name="webhookId",
     *         in="path",
     *         description="ID del webhook a eliminar",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="3fa85f64-5717-4562-b3fc-2c963f66afa6")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Webhook eliminado exitosamente (sin contenido)"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Webhook no encontrado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado - API key inválida o faltante",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autenticado")
     *         )
     *     ),
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
                null,
                'Webhook eliminado exitosamente',
                204
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al eliminar webhook: ' . $e->getMessage(),
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de webhooks obtenida exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/ListWebhooksResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado - API key inválida o faltante",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autenticado")
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

            return $this->successResponse(
                $webhooks['data'],
                'Webhooks obtenidos exitosamente'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al listar webhooks: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Post(
     *     path="/buro-ingresos/webhook",
     *     summary="Recibir notificación de webhook",
     *     description="Recibe notificaciones en tiempo real de Buró de Ingresos sobre el estado de verificaciones.",
     *     tags={"Buró de ingresos - Webhooks"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payload enviado por Buró de Ingresos",
     *         @OA\JsonContent(ref="#/components/schemas/WebhookEvent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook procesado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="received", ref="#/components/schemas/WebhookEvent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Header de autenticación inválido o ausente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Header BURO_INGRESOS_WEBHOOK_KEY inválido o ausente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     ),
     *     @OA\Parameter(
     *         name="BURO_INGRESOS_WEBHOOK_KEY",
     *         in="header",
     *         required=true,
     *         description="Clave secreta configurada para validar la autenticidad del webhook",
     *         @OA\Schema(type="string", example="8f08e11719e2ffa2daf29b4911d324b44453c75a90070e03")
     *     )
     * )
     */
    public function receiveWebhook(Request $request)
    {
        try {
            // Validar header de autenticación
            $webhookKey = $request->header('BURO_INGRESOS_WEBHOOK_KEY');
            $expectedKey = env('BURO_INGRESOS_WEBHOOK_KEY');

            if ($webhookKey !== $expectedKey) {
                return response()->json([
                    'status' => 'ok',
                    'message' => 'received'
                ], 200);
            }

            $payload = $request->all();

            $service = new BuroDeIngresosWebhookService(
                app(BuroDeIngresosService::class)
            );

            // Agregar los comandos que quieras
            $service->setPayload($payload)
                ->addCommand(ProcessCandidatoDatos::class)
                ->addCommand(ProcessCandidatoDatosExtra::class)
                ->addCommand(ProcessCandidatoLaborales::class)
                ->addCommand(ProcessDocumentosSA::class);


            // Ejecutar todo
            $result = $service->execute();

            return response()->json([
                'status' => 'ok',
                'received' => $payload,
                'processed' => $result
            ], 200);
        } catch (\Exception $e) {
            Log::channel('buro_ingreso')->error('Error crítico en webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno procesando webhook',
                'received' => $request->all()
            ], 200);
        }
    }
}
