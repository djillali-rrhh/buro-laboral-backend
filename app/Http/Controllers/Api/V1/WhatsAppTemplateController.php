<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsAppTemplateController extends Controller
{
    use ApiResponse;

    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
    {
        return $this->successResponse(WhatsAppTemplate::all(), 'Plantillas locales obtenidas con éxito.');
    }

public function store(Request $request)
    {
        try {
            Log::info('Recibida petición para crear plantilla. Datos crudos:', $request->all());

            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
                'language' => 'required|string|max:10',
                'category' => ['required', 'string', Rule::in(['AUTHENTICATION', 'MARKETING', 'UTILITY'])],
                'components' => 'required|array',
            ]);
            
            $payloadParaMeta = $validatedData;
            $payloadParaMeta['components'] = $request->input('components');

            if ($request->has('allow_category_change')) {
                $payloadParaMeta['allow_category_change'] = $request->boolean('allow_category_change');
            }

            Log::info('Payload final que se enviará a Meta:', $payloadParaMeta);

            $response = $this->whatsAppService->createTemplate($payloadParaMeta);
            Log::info('Respuesta de la API de Meta:', $response->json() ?? ['body' => $response->body()]);

            if (!$response->successful()) {
                return $this->errorResponse('Error al crear la plantilla en Meta.', 400, $response->json());
            }

            $template = WhatsAppTemplate::create([
                'name' => $validatedData['name'],
                'language' => $validatedData['language'],
                'category' => $validatedData['category'],
                'status' => 'PENDING',
                'variables' => json_encode($payloadParaMeta['components']),
            ]);

            Log::info('Plantilla guardada localmente con éxito.', ['id' => $template->id]);

            return $this->successResponse($template, 'Plantilla enviada a Meta para aprobación y guardada localmente.', 201);

        } catch (ValidationException $e) {
            Log::error('Error de validación al crear plantilla.', ['errors' => $e->errors()]);
            return $this->errorResponse('Datos de validación inválidos.', 422, $e->errors());
        } catch (\Exception $e) {
            Log::critical('Error inesperado al crear plantilla.', ['exception' => $e->getMessage()]);
            return $this->errorResponse('Ocurrió un error inesperado en el servidor.', 500, ['details' => $e->getMessage()]);
        }
    }

    public function show(WhatsAppTemplate $template)
    {
        return $this->successResponse($template, 'Plantilla obtenida con éxito.');
    }

    public function destroy(WhatsAppTemplate $template)
    {
        $template->delete();
        return $this->successResponse(null, 'Plantilla eliminada localmente.');
    }
}