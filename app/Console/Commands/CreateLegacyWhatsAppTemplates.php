<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class CreateLegacyWhatsAppTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:create-legacy-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea las plantillas de WhatsApp del archivo legacy en la plataforma de Meta vía API, saltando las que ya existen.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsappService)
    {
        $this->info('Iniciando la creación de plantillas legacy en Meta...');

        $this->line('-> Verificando plantillas existentes en Meta...');
        $existingTemplatesResponse = $whatsappService->getTemplates();

        if (!$existingTemplatesResponse || !$existingTemplatesResponse->successful()) {
            $this->error('Error: No se pudo obtener la lista de plantillas existentes desde Meta. Abortando.');
            $this->error('Asegúrate de que tu WHATSAPP_ACCESS_TOKEN y WHATSAPP_BUSINESS_ACCOUNT_ID son correctos.');
            return 1;
        }

        $existingTemplateNames = collect($existingTemplatesResponse->json()['data'] ?? [])->pluck('name')->toArray();
        $this->info('   => Se encontraron ' . count($existingTemplateNames) . ' plantillas existentes.');
        $this->warn('Este proceso puede tardar. Se enviará cada plantilla nueva a la API de Meta para su creación y aprobación.');

        $legacyTemplates = [
            'candidato_meet' => ['name' => 'candidato_meet', 'language' => 'es_MX', 'category' => 'UTILITY', 'body' => 'Tu entrevista ha sido agendada para el {{1}}. Puedes unirte a través del siguiente enlace.', 'button_url' => 'https://meet.google.com/{{1}}', 'button_text' => 'Unirse a la reunión'],
            'aviso_cita_agendada_por_cliente' => ['name' => 'aviso_cita_agendada_por_cliente', 'language' => 'es_MX', 'category' => 'UTILITY', 'body' => 'El cliente {{1}} ha agendado una cita para el {{2}}. Puedes ver los detalles aquí.', 'button_url' => 'https://tuportal.com/citas/{{1}}', 'button_text' => 'Ver Detalles'],
            'encuesta_homeoffice' => ['name' => 'encuesta_homeoffice', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, por favor completa la siguiente encuesta para la empresa {{2}}. Gracias.', 'button_url' => 'https://tuportal.com/encuestas/{{1}}', 'button_text' => 'Completar Encuesta'],
            'agendar_cita' => ['name' => 'agendar_cita', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola, por favor agende su cita con la empresa {{1}}. Siga el enlace.', 'button_url' => 'https://tuportal.com/agendar/{{1}}', 'button_text' => 'Agendar Cita'],
            'logistica_asignar' => ['name' => 'logistica_asignar', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Se le ha asignado la logística para el cliente {{1}}. Por favor, revise los detalles.'],
            'recordatorio_firmar_aviso' => ['name' => 'recordatorio_firmar_aviso', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Le recordamos que tiene pendiente firmar nuestro aviso de privacidad.', 'button_url' => 'https://tuportal.com/aviso/{{1}}', 'button_text' => 'Firmar Aviso'],
            'recordatorio_cita' => ['name' => 'recordatorio_cita', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Le recordamos que tiene una cita programada. ¡No falte!'],
            'recordatorio_cita_meet' => ['name' => 'recordatorio_cita_meet', 'language' => 'es_ES', 'category' => 'UTILITY', 'body' => 'Le recordamos su reunión virtual. Puede unirse usando el siguiente botón.', 'button_url' => 'https://meet.google.com/{{1}}', 'button_text' => 'Unirse a Meet'],
            'subir_documentos_client' => ['name' => 'subir_documentos_client', 'language' => 'es', 'category' => 'UTILITY', 'header_image' => true, 'body' => 'Hola {{1}}, por favor sube tus documentos en el siguiente enlace.', 'button_url' => 'https://tuportal.com/clientes/{{1}}', 'button_text' => 'Subir Documentos'],
            'subir_documentos_candidato' => ['name' => 'subir_documentos_candidato', 'language' => 'es', 'category' => 'UTILITY', 'header_image' => true, 'body' => 'Te contactamos de parte de la empresa {{1}} para solicitarte que subas tu documentación en el siguiente portal.', 'button_url' => 'https://tuportal.com/candidatos/{{1}}', 'button_text' => 'Subir Documentos'],
            'reagendar_cita' => ['name' => 'reagendar_cita', 'language' => 'es_MX', 'category' => 'UTILITY', 'body' => 'Hubo un cambio de planes. Por favor, reagende su cita con {{1}}.', 'button_url' => 'https://tuportal.com/reagendar/{{1}}', 'button_text' => 'Reagendar Cita'],
            'recordatorio_referencias_personales' => ['name' => 'recordatorio_referencias_personales', 'language' => 'es_MX', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, te recordamos completar tus referencias personales: {{2}}.', 'button_url' => 'https://tuportal.com/referencias/{{1}}', 'button_text' => 'Completar Referencias'],
            'no_asistio' => ['name' => 'no_asistio', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Informamos que el candidato {{2}} no asistió a la cita con el cliente {{1}}.'],
            'falla_conexion' => ['name' => 'falla_conexion', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Informamos que el candidato {{2}} tuvo una falla de conexión en la cita con el cliente {{1}}.'],
            'datos_postulacion' => ['name' => 'datos_postulacion', 'language' => 'es', 'category' => 'UTILITY', 'header_text' => '{{1}}', 'body' => 'Resumen de tu postulación: {{1}}.', 'button_url' => 'https://tuportal.com/postulacion/{{1}}', 'button_text' => 'Ver Postulación'],
            'toque2' => ['name' => 'toque2', 'language' => 'es', 'category' => 'MARKETING', 'body' => 'Hola {{1}}, te saluda {{2}}. ¿Tuviste oportunidad de revisar nuestra propuesta?'],
            'aviso_cita_agendada' => ['name' => 'aviso_cita_agendada', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'El estado de tu cita ha cambiado a: {{1}}. Puedes subir tus documentos aquí.', 'button_url' => 'https://tuportal.com/documentos/{{1}}', 'button_text' => 'Subir Documentos'],
            'candidato_proceso' => ['name' => 'candidato_proceso', 'language' => 'es', 'category' => 'UTILITY', 'header_text' => '{{1}}', 'body' => 'Has avanzado en el proceso de selección para la empresa {{1}}. Completa el siguiente paso.', 'button_url' => 'https://tuportal.com/proceso/{{1}}', 'button_text' => 'Siguiente Paso'],
            'evaluacion_ejecutivo' => ['name' => 'evaluacion_ejecutivo', 'language' => 'es', 'category' => 'MARKETING', 'body' => 'Nos gustaría conocer tu opinión sobre el servicio recibido por parte de {{1}} de la empresa {{2}}.', 'button_url' => 'https://tuportal.com/evaluacion/{{1}}', 'button_text' => 'Evaluar Servicio'],
            'confirmacion_asistencia' => ['name' => 'confirmacion_asistencia', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, por favor confirma tu asistencia a la cita del {{2}}.', 'button_url' => 'https://tuportal.com/confirmar/{{1}}', 'button_text' => 'Confirmar Asistencia'],
            'agendar__recordatorio' => ['name' => 'agendar__recordatorio', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, te recordamos agendar tu cita en el siguiente enlace.', 'button_url' => 'https://tuportal.com/recordatorio/{{1}}', 'button_text' => 'Agendar'],
            'agendarcita' => ['name' => 'agendarcita', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, agenda tu cita con la empresa {{2}}.', 'button_url' => 'https://tuportal.com/agendar/cita/{{1}}', 'button_text' => 'Agendar'],
            'documentacion_vd' => ['name' => 'documentacion_vd', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, por favor carga tus documentos aquí: {{2}}.', 'button_url' => 'https://tuportal.com/documentos_vd/{{1}}', 'button_text' => 'Cargar Documentos'],
            'firma_de_aviso_de_privacidad' => ['name' => 'firma_de_aviso_de_privacidad', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, por favor firma nuestro aviso de privacidad.', 'button_url' => 'https://tuportal.com/firma_aviso/{{1}}', 'button_text' => 'Firmar Aviso'],
            'notificacion_videollamada_cliente' => ['name' => 'notificacion_videollamada_cliente', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Hola {{1}}, el estado de tu videollamada es: {{2}}. Fecha original: {{3}}.', 'button_url' => 'https://tuportal.com/videollamada/{{1}}', 'button_text' => 'Ver Detalles'],
            'proceso_finalizado_candidato' => ['name' => 'proceso_finalizado_candidato', 'language' => 'es', 'category' => 'UTILITY', 'body' => 'Tu proceso ha finalizado. Resumen: {{1}}.'],
        ];

        foreach ($legacyTemplates as $template) {
            $templateName = strtolower(str_replace([' ', '_ '], ['_', ''], $template['name']));
            
            if (in_array($templateName, $existingTemplateNames)) {
                $this->warn("   -> La plantilla '{$templateName}' ya existe en Meta. Saltando.");
                continue;
            }

            $this->line("-> Procesando nueva plantilla: {$template['name']}");

            $components = [];
            
            $bodyExample = null;
            $headerExample = null;

            if (isset($template['header_image'])) {
                $components[] = ['type' => 'HEADER', 'format' => 'IMAGE', 'example' => ['header_url' => ["https://www.tuempresa.com/placeholder.png"]]];
            } elseif (isset($template['header_text'])) {
                 $headerText = $template['header_text'];
                 if (str_contains($headerText, '{{1}}')) {
                    $headerExample = ['header_text' => ["Texto de ejemplo"]];
                 }
                $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $headerText, 'example' => $headerExample];
            }

            if (isset($template['body'])) {
                $bodyText = $template['body'];
                $bodyVarsCount = substr_count($bodyText, '{{');
                if ($bodyVarsCount > 0) {
                    $exampleValues = [];
                    for ($i = 1; $i <= $bodyVarsCount; $i++) { $exampleValues[] = "valor de ejemplo {$i}"; }
                    $bodyExample = ['body_text' => [$exampleValues]];
                }
                $components[] = ['type' => 'BODY', 'text' => $bodyText, 'example' => $bodyExample];
            }

            if (isset($template['button_url'])) {
                $buttonExample = null;
                $urlTemplate = $template['button_url'];
                if (str_contains($urlTemplate, '{{1}}')) {
                    $buttonExample = ["ejemplo-url-dinamica"];
                }
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => [['type' => 'URL', 'text' => $template['button_text'], 'url' => $urlTemplate, 'example' => $buttonExample]]
                ];
            }

            // Evita enviar componentes vacíos si la plantilla solo tiene, por ejemplo, un botón
            if (empty($components)) {
                if (isset($template['button_url'])) {
                     $buttonExample = null;
                    $urlTemplate = $template['button_url'];
                    if (str_contains($urlTemplate, '{{1}}')) {
                        $buttonExample = ["ejemplo-url-dinamica"];
                    }
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => [['type' => 'URL', 'text' => $template['button_text'], 'url' => $urlTemplate, 'example' => $buttonExample]]
                    ];
                }
            }


            $payload = [
                'name' => $templateName,
                'language' => $template['language'],
                'category' => $template['category'],
                'components' => $components,
                'allow_category_change' => true,
            ];

            $response = $whatsappService->createTemplate($payload);

            if ($response->successful()) {
                $this->info("   => Plantilla '{$template['name']}' enviada para creación. ID: " . ($response->json()['id'] ?? 'N/A'));
            } else {
                $this->error("   => Error al crear '{$template['name']}': " . $response->body());
            }
        }

        $this->info('Proceso de creación de plantillas finalizado.');
        $this->comment('Recuerda revisar el Administrador de WhatsApp para ver el estado de aprobación de cada plantilla.');
        return 0;
    }
}

