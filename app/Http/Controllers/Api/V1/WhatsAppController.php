<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppController extends Controller
{
    use ApiResponse;

    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function sendTemplate(Request $request)
    {
        try {
            $data = $request->validate([
                'templateName' => 'required|string',
                'to' => 'required|string',
                'variables' => 'sometimes|array',
                'languageCode' => 'sometimes|string',
            ]);

            $result = $this->whatsAppService->sendTemplateMessage(
                $data['templateName'],
                $data['to'],
                $data['variables'] ?? [],
                $data['languageCode'] ?? 'es_MX'
            );

            if (isset($result['error'])) {
                // Mensaje genérico para el usuario
                return $this->errorResponse(
                    'Hubo un error con el servicio de WhatsApp',
                    400,
                    $result // El error detallado de Meta se mantiene para depuración
                );
            }

            return $this->successResponse($result, 'Template message sent successfully.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500, ['details' => $e->getMessage()]);
        }
    }

    public function sendText(Request $request)
    {
        try {
            $data = $request->validate([
                'to' => 'required|string',
                'message' => 'required|string'
            ]);

            $result = $this->whatsAppService->sendTextMessage($data['to'], $data['message']);

            if (isset($result['error'])) {
                // Mensaje genérico para el usuario
                return $this->errorResponse(
                    'Hubo un error con el servicio de WhatsApp',
                    400,
                    $result // El error detallado de Meta se mantiene para depuración
                );
            }

            return $this->successResponse($result, 'Text message sent successfully.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500, ['details' => $e->getMessage()]);
        }
    }

    public function receiveWebhook(Request $request)
    {
        if ($request->has('hub_challenge') && $request->query('hub_verify_token') === env('WHATSAPP_VERIFY_TOKEN')) {
            return response($request->query('hub_challenge'), 200);
        }

        $payload = $request->all();
        Log::channel('whatsapp_api')->info('Webhook received', ['payload' => $payload]);

        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            $this->whatsAppService->logMessage(
                $message['from'], null, $message['type'],
                $message['text'] ?? [], 'received', $payload, 'inbound'
            );
        }

        return $this->successResponse([], 'Webhook processed successfully.');
    }
}