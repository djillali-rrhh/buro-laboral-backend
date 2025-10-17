<?php

namespace App\Console\Commands;

use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppTemplates extends Command
{
    /**
     * El nombre y la firma del comando de la consola.
     *
     * @var string
     */
    protected $signature = 'whatsapp:sync-templates';

    /**
     * La descripción del comando de la consola.
     *
     * @var string
     */
    protected $description = 'Sincroniza las plantillas de mensajes de WhatsApp desde la API de Meta a la base de datos local.';

    /**
     * Ejecuta el comando de la consola.
     */
    public function handle(WhatsAppService $whatsappService)
    {
        $this->info('Iniciando la sincronización de plantillas de WhatsApp...');

        try {
            $response = $whatsappService->getTemplates();

            if (!($response instanceof Response) || !$response->successful()) {
                $this->error('No se pudo obtener la lista de plantillas desde la API de Meta.');
                $errorDetails = ($response instanceof Response) ? $response->json() : ['error' => 'Respuesta inesperada del servicio.'];
                Log::channel('whatsapp_api')->error('Fallo al obtener plantillas', $errorDetails);
                return 1;
            }

            $templates = $response->json()['data'] ?? [];

            if (empty($templates)) {
                $this->warn('No se encontraron plantillas aprobadas en la cuenta de WhatsApp Business.');
                return 0;
            }

            $count = 0;
            foreach ($templates as $templateData) {
                WhatsAppTemplate::updateOrCreate(
                    [
                        'name' => $templateData['name'],
                        'language' => $templateData['language']
                    ],
                    [
                        'category' => $templateData['category'],
                        'status' => $templateData['status'],
                        'variables' => json_encode($templateData['components'] ?? []),
                    ]
                );
                $count++;
            }

            $this->info("Sincronización completada. Se procesaron {$count} plantillas.");
            return 0;

        } catch (\Exception $e) {
            $this->error('Ocurrió un error inesperado durante la sincronización.');
            $this->error($e->getMessage());
            Log::channel('whatsapp_api')->critical('Error fatal en SyncWhatsAppTemplates', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}