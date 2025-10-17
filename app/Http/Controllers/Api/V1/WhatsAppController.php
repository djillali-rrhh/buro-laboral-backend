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
                'template_name' => 'required|string',
                'to' => 'required|string',
                'components' => 'present|array',
                'language_code' => 'sometimes|string',
            ]);

            $result = $this->whatsAppService->sendTemplateMessage(
                $data['template_name'],
                $data['to'],
                $data['components'],
                $data['language_code'] ?? 'es_MX'
            );

            if (isset($result['error'])) {
                return $this->errorResponse('Hubo un error con el servicio de WhatsApp', 400, $result);
            }

            return $this->successResponse($result, 'Template message sent successfully.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error("Error sending template: " . $e->getMessage());
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
                return $this->errorResponse('Hubo un error con el servicio de WhatsApp', 400, $result);
            }

            return $this->successResponse($result, 'Text message sent successfully.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500, ['details' => $e->getMessage()]);
        }
    }

    public function getTemplates()
    {
        try {
            $response = $this->whatsAppService->getTemplates();
            if ($response && $response->successful()) {
                return $this->successResponse($response->json()['data'] ?? [], 'Templates retrieved successfully.');
            }
            return $this->errorResponse(
                'Could not retrieve templates from WhatsApp service.', 502,
                $response ? $response->json() : ['error' => 'No response from service']
            );
        } catch (\Exception $e) {
            return $this->errorResponse('An unexpected error occurred.', 500, ['details' => $e->getMessage()]);
        }
    }

    /**
     * Webhook de recepción para WhatsApp Business API.
     * Maneja verificación GET y eventos POST.
     */
    public function receiveWebhook(Request $request)
    {
        if ($request->isMethod('get')) {
            $mode = $request->input('hub.mode') ?? $request->input('hub_mode');
            $token = $request->input('hub.verify_token') ?? $request->input('hub_verify_token');
            $challenge = $request->input('hub.challenge') ?? $request->input('hub_challenge');

            Log::info('Webhook verification attempt', [
                'mode' => $mode,
                'token_received' => $token,
                'token_expected' => config('services.whatsapp.verify_token'),
                'challenge' => $challenge,
                'all_params' => $request->all()
            ]);

            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
                Log::info('✅ Webhook verificado exitosamente');
                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('❌ Verificación de webhook fallida', [
                'token_recibido' => $token,
                'token_esperado' => config('services.whatsapp.verify_token'),
                'match' => $token === config('services.whatsapp.verify_token') ? 'SI' : 'NO'
            ]);
            
            return response()->json(['error' => 'Verification failed'], 403);
        }

        try {
            $payload = $request->all();
            
            if (!isset($payload['object']) || $payload['object'] !== 'whatsapp_business_account') {
                Log::warning('Payload inválido recibido', ['payload' => $payload]);
                return response()->json(['status' => 'ok'], 200);
            }

            Log::info('Webhook recibido', ['payload' => $payload]);

            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];
                    
                    if (!empty($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->whatsAppService->processIncomingMessage($message, $value['metadata'] ?? []);
                        }
                    }

                    if (!empty($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->whatsAppService->processStatusUpdate($status);
                        }
                    }

                    if (!empty($value['errors'])) {
                        foreach ($value['errors'] as $error) {
                            Log::error('Error recibido en webhook', ['error' => $error]);
                        }
                    }
                }
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::critical('Error procesando webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['status' => 'error', 'message' => 'Internal error'], 200);
        }
    }

    public function sendTemplateBatch(Request $request)
    {
        try {
            $data = $request->validate([
                'template_name' => 'required|string',
                'recipients' => 'required|array',
                'recipients.*' => 'string',
                'components' => 'present|array',
                'language_code' => 'sometimes|string',
            ]);

            $results = [];
            $languageCode = $data['language_code'] ?? 'es_MX';

            foreach ($data['recipients'] as $recipient) {
                $result = $this->whatsAppService->sendTemplateMessage(
                    $data['template_name'],
                    $recipient,
                    $data['components'],
                    $languageCode
                );
                
                $results[] = [
                    'to' => $recipient,
                    'response' => $result
                ];
            }

            return $this->successResponse($results, 'Batch template messages processed.');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            Log::error("Error sending template batch: " . $e->getMessage());
            return $this->errorResponse('An unexpected error occurred.', 500, ['details' => $e->getMessage()]);
        }
    }



}