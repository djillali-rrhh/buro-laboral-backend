<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EmailService\EmailService;
use App\Models\EmailLog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Controlador para el envío de correos electrónicos vía API.
 *
 * Permite enviar emails de forma síncrona o asíncrona (cola) con soporte
 * para múltiples estrategias (SMTP, SendGrid), plantillas, adjuntos, etc.
 *
 * @package App\Http\Controllers\Api\V1
 */
class EmailController extends Controller
{
    use ApiResponse;

    protected EmailService $emailService;

    public function __construct()
    {
        $strategy = config('email-service.driver', env('MAIL_EMAIL_SERVICE_DRIVER', 'smtp'));
        $this->emailService = new EmailService($strategy);
    }

    /**
     * Envía un email de forma asíncrona (cola).
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @throws ValidationException
     *
     * @api POST /api/v1/email/queue
     */
    public function queue(Request $request): JsonResponse
    {
        $validator = $this->validateEmailRequest($request);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Errores de validación',
                422,
                $validator->errors()->toArray()
            );
        }

        try {
            $data = $this->prepareEmailData($request);

            // Cambiar estrategia si se especifica
            if ($request->has('strategy')) {
                $this->emailService->setStrategy($request->input('strategy'));
            }

            // Parámetros de cola
            $queue = $request->input('queue_name');
            $delay = $request->input('delay');

            // Envío asíncrono
            $job = $this->emailService->queue($data, $queue, $delay);

            return $this->successResponse([
                'message' => 'Email encolado exitosamente',
                'queued_at' => now()->toIso8601String(),
                'to' => $data['to'],
                'subject' => $data['subject'],
                'queue' => $queue ?? 'default',
                'delay_seconds' => $delay ?? 0,
            ], 'Email encolado para envío asíncrono', 202);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Error al encolar el email: ' . $e->getMessage(),
                500,
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            );
        }
    }


    /**
     * Valida los datos de la solicitud de email.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateEmailRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'from' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'to' => 'required',
            'cc' => 'nullable',
            'bcc' => 'nullable',
            'reply_to' => 'nullable',
            'subject' => 'required|string|max:500',
            'html' => 'nullable|string',
            'text' => 'nullable|string',
            'view' => 'nullable|string|max:255',
            'view_data' => 'nullable|array',
            'attachments' => 'nullable|array',
            'attachments.*.path' => 'required_with:attachments|string',
            'attachments.*.name' => 'nullable|string',
            'attachments.*.type' => 'nullable|string',
            'priority' => 'nullable|integer|min:1|max:5',
            'headers' => 'nullable|array',
            'strategy' => 'nullable|in:smtp,sendgrid',
            'queue_name' => 'nullable|string',
            'delay' => 'nullable|integer|min:0',
            'context_type' => 'nullable|string|max:100',
            'context_id' => 'nullable|integer',
        ]);
    }

    /**
     * Prepara los datos del email desde la request.
     *
     * @param Request $request
     * @return array
     */
    protected function prepareEmailData(Request $request): array
    {
        $data = [
            'from' => $request->input('from'),
            'from_name' => $request->input('from_name'),
            'to' => $request->input('to'),
            'cc' => $request->input('cc'),
            'bcc' => $request->input('bcc'),
            'reply_to' => $request->input('reply_to'),
            'subject' => $request->input('subject'),
            'html' => $request->input('html'),
            'text' => $request->input('text'),
            'view' => $request->input('view'),
            'view_data' => $request->input('view_data'),
            'attachments' => $request->input('attachments'),
            'priority' => $request->input('priority'),
            'headers' => $request->input('headers'),
        ];

        // Remover nulls
        return array_filter($data, fn($value) => $value !== null);
    }
}