<?php

namespace App\Services\BuroDeIngresos;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BuroDeIngresosService
{
    private string $baseUrl = 'https://api.burodeingresos.com';

    private string $apiKey;
    private string $webhookKey;
    private bool $sandbox;
    private ?string $curp = null;
    private ?string $verificationId = null;
    private ?string $ipAddress = null;
    private ?string $privacyNoticeUrl = null;

    public function __construct()
    {
        $this->apiKey = env('BURO_INGRESOS_API_KEY', '');
        $this->webhookKey = env('BURO_INGRESOS_WEBHOOK_KEY', '');
        $this->sandbox = env('BURO_INGRESOS_SANDBOX_MODE', null);
        $this->baseUrl = env('BURO_INGRESOS_API_BASE_URL', $this->baseUrl);

        Log::error('BuroDeIngresosService initialized', [
            "apiKey" => substr($this->apiKey, 0, 4) . '...' . substr($this->apiKey, -4),
            "webhookKey" => substr($this->webhookKey, 0, 4) . '...' . substr($this->webhookKey, -4),
            "baseUrl" => $this->baseUrl,
            "sandbox" => $this->sandbox,
            "sanboxType" => gettype($this->sandbox),
        ]);


        if (empty($this->baseUrl)) {
            throw new \Exception('Base URL de Buró de Ingresos no configurada');
        }

        if (empty($this->apiKey)) {
            throw new \Exception('API Key de Buró de Ingresos no configurada');
        }

        if (empty($this->webhookKey)) {
            throw new \Exception('Webhook Key de Buró de Ingresos no configurada');
        }

        if ($this->sandbox === null) {
            throw new \Exception('Modo Sandbox de Buró de Ingresos no configurado');
        }
    }

    public function setCurp(string $curp): self
    {
        $this->curp = trim($curp);
        return $this;
    }

    public function setVerificationId(string $verificationId): self
    {
        $this->verificationId = $verificationId;
        return $this;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function setPrivacyNoticeUrl(string $privacyNoticeUrl): self
    {
        $this->privacyNoticeUrl = $privacyNoticeUrl;
        return $this;
    }

    /**
     * Obtiene el cliente HTTP configurado con headers
     */
    private function getHttpClient()
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKey,
        ];

        if ($this->sandbox) {
            $headers['x-sandbox'] = 'true';
        }

        return Http::withHeaders($headers)
            ->timeout(30)
            ->connectTimeout(10)
            ->baseUrl($this->baseUrl);
    }

    /**
     *  CONSENTIMIENTOS
     */

    /**
     * Crea un consentimiento
     */
    public function createConsent(): array
    {
        try {
            $response = $this->getHttpClient()->post('/consents', [
                'identifier' => $this->curp,
                'ip_address' => $this->ipAddress,
                'privacy_notice_url' => $this->privacyNoticeUrl,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear consentimiento', [
                'curp' => $this->curp,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    public function createBulkConsents(array $curps): array
    {
        $consents = array_map(function ($curp) {
            return [
                'identifier' => $curp,
                'ip_address' => $this->ipAddress,
                'privacy_notice_url' => $this->privacyNoticeUrl,
            ];
        }, $curps);

        try {
            $response = $this->getHttpClient()->post('/consents/bulk', $consents);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear consentimientos masivos', [
                'curps' => $curps,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }


    /**
     * Lista los consentimientos
     */
    public function listConsents(
        int $page = 1,
        int $itemsPerPage = 100
    ): array {
        try {
            $response = $this->getHttpClient()->get('/consents', [
                'identifier' => $this->curp,
                'page' => $page,
                'items_per_page' => $itemsPerPage,
            ]);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al listar consentimientos', [
                'curp' => $this->curp,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }


    /**
     *  VERIFICACIONES
     */

    /**
     * Crea una verificación
     */
    public function createVerification(
        ?string $externalId = null
    ): array {
        try {

            $params = [
                'identifier' => $this->curp,
            ];
            if ($externalId !== null) {
                $params['external_id'] = $externalId;
            }

            $response = $this->getHttpClient()->post('/verifications', $params);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear verificación', [
                'curp' => $this->curp,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    public function listVerifications(
        int $page = 1,
        int $itemsPerPage = 100,
        ?string $startDate = null
    ): array {
        try {
            $params = [
                'page' => $page,
                'items_per_page' => $itemsPerPage,
            ];

            if ($this->curp !== null) {
                $params['identifier'] = $this->curp;
            }

            if ($startDate !== null) {
                $params['start_date'] = $startDate;
            }

            $response = $this->getHttpClient()->get('/verifications', $params);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al listar verificaciones', [
                'curp' => $this->curp,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Crea verificaciones en masa
     * 
     * @param array<int, array{identifier: string, external_id?: string}> $verifications
     * @return array
     */
    public function createBulkVerifications(array $verifications): array
    {
        try {

            $response = $this->getHttpClient()->post(
                '/verifications/bulk',
                $verifications
            );

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear verificaciones masivas', [
                'verifications_count' => count($verifications),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Obtiene el estado de una verificación
     */
    public function getVerification(): array
    {
        try {
            $response = $this->getHttpClient()->get("/verifications/{$this->verificationId}");

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Error al obtener verificación', [
                'verification_id' => $this->verificationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
            ];
        }
    }


    /**
     * Obtiene el estado de una verificación en masa
     */
    public function getBulkVerificationStatus(string $bulkId): array
    {
        try {
            $response = $this->getHttpClient()->get("/verifications/bulk/{$bulkId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de verificación en masa', [
                'bulk_id' => $bulkId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }


    /**
     * Elimina una verificación específica
     */
    public function deleteVerification(string $verificationId): array
    {
        try {
            $response = $this->getHttpClient()->delete("/verifications/{$verificationId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al eliminar verificación', [
                'verification_id' => $verificationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Elimina una verificación en masa y todas sus verificaciones asociadas
     */
    public function deleteBulkVerification(string $bulkId): array
    {
        try {
            $response = $this->getHttpClient()->delete("/verifications/bulk/{$bulkId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al eliminar verificación en masa', [
                'bulk_id' => $bulkId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * INFORMACIÓN
     */

    /**
     * Obtiene el perfil del candidato
     */
    public function getProfile(): array
    {
        $response = $this->getHttpClient()->get("/profile/{$this->curp}");

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'http_code' => $response->status(),
        ];
    }

    /**
     * Obtiene el historial de empleos del candidato con filtros opcionales
     */
    public function getEmployments(
        int $page = 1,
        int $itemsPerPage = 100,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {

        $queryParams = [
            'page' => $page,
            'items_per_page' => $itemsPerPage,
        ];

        if ($startDate) {
            $queryParams['start_date'] = $startDate;
        }

        if ($endDate) {
            $queryParams['end_date'] = $endDate;
        }

        $response = $this->getHttpClient()->get("/employments/{$this->curp}", $queryParams);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'http_code' => $response->status(),
        ];
    }

    /**
     * Obtiene las invoices del candidato con filtros opcionales
     */
    public function getInvoices(
        ?string $type = null,
        int $page = 1,
        int $itemsPerPage = 100,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {

        $queryParams = [
            'page' => $page,
            'items_per_page' => $itemsPerPage,
        ];

        if ($type) {
            $queryParams['type'] = $type;
        }

        if ($startDate) {
            $queryParams['start_date'] = $startDate;
        }

        if ($endDate) {
            $queryParams['end_date'] = $endDate;
        }

        $response = $this->getHttpClient()->get("/invoices/{$this->curp}", $queryParams);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'http_code' => $response->status(),
        ];
    }

    /**
     * WEBHOOKS
     */
    public function createWebhook(string $endpointUrl, ?string $description = null): array
    {
        try {
            $data = [
                'endpoint_url' => $endpointUrl,
                'authentication' => [
                    'type' => 'custom_headers',
                    'headers' => [
                        'BURO_INGRESOS_WEBHOOK_KEY' => $this->webhookKey
                    ]
                ]
            ];

            if ($description) {
                $data['description'] = $description;
            }

            $response = $this->getHttpClient()->post('/webhooks', $data);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al crear webhook', [
                'endpoint_url' => $endpointUrl,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    public function getWebhook(string $webhookId): array
    {
        try {
            $response = $this->getHttpClient()->get("/webhooks/{$webhookId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Elimina un webhook específico
     */
    public function deleteWebhook(string $webhookId): array
    {
        try {
            $response = $this->getHttpClient()->delete("/webhooks/{$webhookId}");

            return [
                'success' => $response->successful(),
                'data' => $response->status() === 204 ? null : $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al eliminar webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Actualiza un webhook existente
     */
    public function updateWebhook(
        string $webhookId,
        ?string $endpointUrl = null,
        ?bool $isActive = null,
        ?string $description = null
    ): array {
        try {
            $data = [
                "authentication" => [
                    'type' => 'custom_headers',
                    'headers' => [
                        'BURO_INGRESOS_WEBHOOK_KEY' => $this->webhookKey
                    ]
                ]
            ];

            if ($endpointUrl !== null) {
                $data['endpoint_url'] = $endpointUrl;
            }

            if ($isActive !== null) {
                $data['is_active'] = $isActive;
            }

            if ($description !== null) {
                $data['description'] = $description;
            }

            $response = $this->getHttpClient()->patch("/webhooks/{$webhookId}", $data);

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al actualizar webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }

    /**
     * Lista todos los webhooks registrados
     */
    public function listWebhooks(): array
    {
        try {
            $response = $this->getHttpClient()->get('/webhooks');

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Error al listar webhooks', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con la API: ' . $e->getMessage(),
                'http_code' => 0,
            ];
        }
    }
}
