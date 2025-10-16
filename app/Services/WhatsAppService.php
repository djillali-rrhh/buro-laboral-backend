<?php

namespace App\Services;

use App\Models\WhatsAppMessageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $phoneNumberId;
    protected $accessToken;
    protected $apiBase;

    public function __construct()
    {
        $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
        $this->accessToken = env('WHATSAPP_ACCESS_TOKEN');
        $this->apiBase = env('WHATSAPP_API_BASE');
    }

    public function sendTemplateMessage($templateName, $to, $variables, $languageCode = 'es_MX')
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $this->formatVariables($variables)
                    ]
                ]
            ]
        ];

        return $this->sendMessage($payload, 'template', $templateName, $variables);
    }

    public function sendTextMessage($to, $message)
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];

        return $this->sendMessage($payload, 'text');
    }

    public function getTemplates()
    {
        // Lógica para obtener plantillas desde Meta API.
        return [];
    }

    protected function sendMessage($payload, $type, $templateName = null, $variables = null)
    {
        $url = "{$this->apiBase}/{$this->phoneNumberId}/messages";

        try {
            $response = Http::withToken($this->accessToken)->post($url, $payload);

            $this->logMessage(
                $payload['to'], $templateName, $type, $variables,
                $response->successful() ? 'sent' : 'failed',
                $response->json(), 'outbound'
            );

            Log::channel('whatsapp_api')->info('Message sent', [
                'request' => $payload, 'response' => $response->json()
            ]);

            return $response->json();
        } catch (\Exception $e) {
            $this->logMessage(
                $payload['to'], $templateName, $type, $variables,
                'error', ['error' => $e->getMessage()], 'outbound'
            );

            Log::channel('whatsapp_api')->error('Error sending message', [
                'request' => $payload, 'error' => $e->getMessage()
            ]);

            return ['error' => $e->getMessage()];
        }
    }

    protected function formatVariables($variables)
    {
        return array_map(fn($v) => ['type' => 'text', 'text' => $v], $variables);
    }

    public function logMessage($to, $templateName, $type, $variables, $status, $response, $direction)
    {
        WhatsAppMessageLog::create([
            'to' => $to, 'template_name' => $templateName,
            'message_type' => $type, 'variables' => $variables,
            'status' => $status, 'response_json' => $response,
            'direction' => $direction,
        ]);
    }
}