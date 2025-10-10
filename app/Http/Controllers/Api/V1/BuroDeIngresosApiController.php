<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BuroDeIngresosService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BuroDeIngresosApiController extends Controller
{
    use ApiResponse;

    /**
     * CONSENTIMIENTOS
     */

    /**
     * Crea un consentimiento
     */
    public function createConsent(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'required|string|size:18',
            'privacy_notice_url' => 'required|url',
        ]);

        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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

    public function createBulkConsents(Request $request)
    {
        $validated = $request->validate([
            'curps' => 'required|array|min:1',
            'curps.*' => 'required|string|size:18',
            'privacy_notice_url' => 'required|url',
        ]);

        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
            $buroService = (new BuroDeIngresosService(sandbox: true));

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
     * Crea una verificación
     */
    public function createVerification(Request $request)
    {
        $validated = $request->validate([
            'curp' => 'required|string|size:18',
        ]);

        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
     * Lista las verificaciones
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
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Crea verificaciones en masa
     */
    public function createBulkVerifications(Request $request)
    {
        $validated = $request->validate([
            'verifications' => 'required|array|min:1|max:100',
            'verifications.*.identifier' => 'required|string|size:18',
            'verifications.*.external_id' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Obtiene el estado de una verificación
     */
    public function getVerification(string $verificationId)
    {
        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
                ->setVerificationId($verificationId);

            $verification = $buroService->getVerification();

            return $this->successResponse($verification);
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener verificación: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el estado de una verificación en masa
     */
    public function getBulkVerificationStatus(string $bulkId)
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Elimina una verificación específica
     */
    public function deleteVerification(string $verificationId)
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Elimina una verificación en masa y todas sus verificaciones asociadas
     */
    public function deleteBulkVerification(string $bulkId)
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Obtiene las facturas del candidato desde Buró de Ingresos
     */
    public function getInvoices(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
     * Obtiene el perfil del candidato desde Buró de Ingresos
     */
    public function getProfile(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
     * Obtiene el historial de empleos del candidato
     */
    public function getEmployments(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
     * Obtiene perfil, empleos e invoices del candidato
     * Este método no forma parte de la API original, es un método auxiliar
     */
    public function getCandidateData(string $identifier)
    {
        try {
            $buroService = (new BuroDeIngresosService(sandbox: true))
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
    public function createWebhook(Request $request)
    {
        $validated = $request->validate([
            'endpoint_url' => 'required|url',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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

    public function getWebhook(string $webhookId)
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Elimina un webhook específico
     */
    public function deleteWebhook(string $webhookId)
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Actualiza un webhook existente
     */
    public function updateWebhook(Request $request, string $webhookId)
    {
        $validated = $request->validate([
            'endpoint_url' => 'sometimes|url',
            'is_active' => 'sometimes|boolean',
            'description' => 'sometimes|string|max:255',
        ]);

        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
     * Lista todos los webhooks registrados
     */
    public function listWebhooks()
    {
        try {
            $buroService = new BuroDeIngresosService(sandbox: true);

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
