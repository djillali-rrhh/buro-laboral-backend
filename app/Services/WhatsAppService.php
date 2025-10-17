<?php

namespace App\Services;

use App\Models\WhatsAppMessageLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $token;
    protected string $baseUrl;
    protected string $phoneNumberId;
    protected string $businessAccountId;

    public function __construct()
    {
        $this->token = config('services.whatsapp.access_token');
        $this->baseUrl = config('services.whatsapp.api_base');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->businessAccountId = config('services.whatsapp.business_account_id');
    }

    public function sendTemplateMessage(string $templateName, string $to, array $components, string $languageCode = 'es_MX')
    {
        $payload = [
            'messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template',
            'template' => ['name' => $templateName, 'language' => ['code' => $languageCode], 'components' => $components,],
        ];
        return $this->sendMessage($payload);
    }

    public function sendTextMessage(string $to, string $message)
    {
        $payload = [
            'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $to, 'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message],
        ];
        return $this->sendMessage($payload);
    }
    
    public function getTemplates(): ?Response
    {
        $url = "{$this->baseUrl}/{$this->businessAccountId}/message_templates";
        try {
            return Http::withToken($this->token)->get($url);
        } catch (\Exception $e) {
            Log::channel('whatsapp_api')->error('Error al obtener plantillas', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    public function createTemplate(array $payload): Response
    {
        $url = "{$this->baseUrl}/{$this->businessAccountId}/message_templates";
        return Http::withToken($this->token)->post($url, $payload);
    }

    private function sendMessage(array $payload)
    {
        $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";
        try {
            $response = Http::withToken($this->token)->post($url, $payload);
            $this->logMessage($payload, $response->successful() ? 'accepted' : 'failed', $response->json(), 'outbound');
            return $response->json();
        } catch (\Exception $e) {
            Log::channel('whatsapp_api')->error('Error al enviar mensaje a WhatsApp', ['error' => $e->getMessage(), 'payload' => $payload]);
            $this->logMessage($payload, 'error', ['error' => $e->getMessage()], 'outbound');
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Loguea un evento (mensaje enviado o recibido).
     */
    public function logMessage(array $payload, string $status, ?array $response, string $direction)
    {
        WhatsAppMessageLog::create([
            'to' => $payload['to'] ?? ($payload['from'] ?? 'N/A'),
            'template_name' => $payload['template']['name'] ?? null,
            'message_type' => $payload['type'],
            'variables' => $payload,
            'status' => $status,
            'response_json' => $response,
            'direction' => $direction,
        ]);
    }

    /**
     * Procesa una actualización de estado (sent, delivered, read) y actualiza el log original.
     */
    public function logStatusUpdate(array $statusData)
    {
        $wamid = $statusData['id'];
        $newStatus = $statusData['status'];

        $messageLog = WhatsAppMessageLog::where('response_json->messages->0->id', $wamid)->first();

        if ($messageLog) {
            $messageLog->status = $newStatus;
            $messageLog->save();
            Log::channel('whatsapp_api')->info("Estado del mensaje {$wamid} actualizado a '{$newStatus}'.");
        } else {
             WhatsAppMessageLog::create([
                'to' => $statusData['recipient_id'],
                'message_type' => 'status_update',
                'status' => $newStatus,
                'response_json' => $statusData,
                'direction' => 'inbound',
                'variables' => ['wamid' => $wamid]
            ]);
            Log::channel('whatsapp_api')->warning("Se recibió una actualización de estado para un wamid desconocido: {$wamid}.");
        }
    }

    /**
 * Procesa un mensaje entrante del webhook.
 */
public function processIncomingMessage(array $message, array $metadata)
{
    try {
        $messageType = $message['type'] ?? 'unknown';
        $from = $message['from'] ?? 'unknown';
        $messageId = $message['id'] ?? null;
        $timestamp = $message['timestamp'] ?? now()->timestamp;

        $messageContent = $this->extractMessageContent($message, $messageType);

        WhatsAppMessageLog::create([
            'to' => $from,
            'template_name' => null,
            'message_type' => $messageType,
            'variables' => [
                'message_id' => $messageId,
                'from' => $from,
                'timestamp' => $timestamp,
                'content' => $messageContent,
                'context' => $message['context'] ?? null,
                'metadata' => $metadata,
                'raw_message' => $message
            ],
            'status' => 'received',
            'response_json' => $message,
            'direction' => 'inbound',
        ]);

        Log::channel('whatsapp_api')->info("Mensaje entrante procesado", [
            'from' => $from,
            'type' => $messageType,
            'message_id' => $messageId
        ]);

    } catch (\Exception $e) {
        Log::channel('whatsapp_api')->error('Error procesando mensaje entrante', [
            'error' => $e->getMessage(),
            'message' => $message
        ]);
    }
}

/**
 * Extrae el contenido del mensaje según su tipo.
 */
private function extractMessageContent(array $message, string $type): mixed
{
    return match($type) {
        'text' => $message['text']['body'] ?? null,
        'image' => [
            'id' => $message['image']['id'] ?? null,
            'mime_type' => $message['image']['mime_type'] ?? null,
            'caption' => $message['image']['caption'] ?? null
        ],
        'video' => [
            'id' => $message['video']['id'] ?? null,
            'mime_type' => $message['video']['mime_type'] ?? null,
            'caption' => $message['video']['caption'] ?? null
        ],
        'audio' => [
            'id' => $message['audio']['id'] ?? null,
            'mime_type' => $message['audio']['mime_type'] ?? null
        ],
        'document' => [
            'id' => $message['document']['id'] ?? null,
            'filename' => $message['document']['filename'] ?? null,
            'mime_type' => $message['document']['mime_type'] ?? null,
            'caption' => $message['document']['caption'] ?? null
        ],
        'location' => [
            'latitude' => $message['location']['latitude'] ?? null,
            'longitude' => $message['location']['longitude'] ?? null,
            'name' => $message['location']['name'] ?? null,
            'address' => $message['location']['address'] ?? null
        ],
        'contacts' => $message['contacts'] ?? null,
        'button' => [
            'text' => $message['button']['text'] ?? null,
            'payload' => $message['button']['payload'] ?? null
        ],
        'interactive' => $message['interactive'] ?? null,
        'sticker' => [
            'id' => $message['sticker']['id'] ?? null,
            'mime_type' => $message['sticker']['mime_type'] ?? null
        ],
        'reaction' => [
            'message_id' => $message['reaction']['message_id'] ?? null,
            'emoji' => $message['reaction']['emoji'] ?? null
        ],
        'order' => $message['order'] ?? null,
        'system' => $message['system'] ?? null,
        default => $message
    };
}

/**
 * Procesa una actualización de estado del webhook.
 * Estados posibles: sent, delivered, read, failed
 */
public function processStatusUpdate(array $status)
{
    try {
        $wamid = $status['id'] ?? null;
        $newStatus = $status['status'] ?? 'unknown';
        $recipientId = $status['recipient_id'] ?? 'unknown';
        $timestamp = $status['timestamp'] ?? now()->timestamp;

        if (!$wamid) {
            Log::channel('whatsapp_api')->warning('Status update sin ID de mensaje', ['status' => $status]);
            return;
        }

        $messageLog = WhatsAppMessageLog::whereRaw(
            "JSON_EXTRACT(response_json, '$.messages[0].id') = ?", 
            [$wamid]
        )->first();

        if ($messageLog) {
            $messageLog->update([
                'status' => $newStatus,
                'variables' => array_merge(
                    $messageLog->variables ?? [],
                    ['last_status_update' => $timestamp]
                )
            ]);

            Log::channel('whatsapp_api')->info("Estado actualizado", [
                'wamid' => $wamid,
                'old_status' => $messageLog->status,
                'new_status' => $newStatus
            ]);
        } else {
            WhatsAppMessageLog::create([
                'to' => $recipientId,
                'template_name' => null,
                'message_type' => 'status_update',
                'variables' => [
                    'wamid' => $wamid,
                    'status_data' => $status
                ],
                'status' => $newStatus,
                'response_json' => $status,
                'direction' => 'inbound',
            ]);

            Log::channel('whatsapp_api')->warning("Status update para mensaje desconocido", [
                'wamid' => $wamid,
                'status' => $newStatus
            ]);
        }

    } catch (\Exception $e) {
        Log::channel('whatsapp_api')->error('Error procesando status update', [
            'error' => $e->getMessage(),
            'status' => $status
        ]);
    }
}

}