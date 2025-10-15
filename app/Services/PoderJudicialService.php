<?php

namespace App\Services;

use App\Helpers\RalUtilidades;
use App\Models\BusquedaRal;
use App\Models\ExpedienteRal;
use App\Models\ExpedienteRalParte;
use App\Models\AcuerdoRal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

/**
 * Servicio de Negocio para interactuar con la API de Poder Judicial Virtual (PJVM).
 * Se encarga de la lógica de consumo de la API, extracción de datos y persistencia.
 * Equivalencia Legacy: Funcionalidad replicada de la clase PoderJudicialVirtual.php
 * y el flujo de guardado de PoderJudciaTareaProgramada.php.
 */
class PoderJudicialService
{
    /**
     * Busca por CURP en la API externa y persiste los datos en la base de datos local.
     * Equivalencia Legacy: Método buscarAuto, searchByCURP y flujo de persistencia.
     *
     * @param array $validatedData Datos validados del request.
     * @return array La respuesta completa de la API externa.
     * @throws ConnectionException|Throwable
     */
    public function searchAndPersistByCurp(array $validatedData): array
    {
        Log::info('Iniciando búsqueda por CURP.', ['curp' => $validatedData['curp']]);
        $endpoint = config('services.poder_judicial.base_uri') . '/curp';
        Log::debug('Consumiendo endpoint de CURP.', ['endpoint' => $endpoint]);

        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->post($endpoint, $validatedData);

        $response->throw();
        $responseData = $response->json();
        Log::debug('Respuesta JSON cruda de la API recibida.', ['body' => $responseData]);
        
        $sourceData = [];
        if (isset($responseData['data'])) {
            $sourceData = $responseData['data'];
        } elseif (isset($responseData['errors'])) {
            $sourceData = $responseData['errors'];
        } elseif (isset($responseData['curp_info'])) {
            $sourceData = $responseData; 
        }

        $curpInfo = $sourceData['curp_info'] ?? [];
        Log::info("sourceData (byCurp) = " . json_encode($sourceData));
        
        if (!empty($curpInfo)) {
            $busquedaData = [
                'Nombres' => $curpInfo['name'] ?? '',
                'Apellidos' => trim(($curpInfo['firstlastname'] ?? '') . ' ' . ($curpInfo['secondlastname'] ?? '')),
                'CURP' => $curpInfo['curp'] ?? $validatedData['curp'],
                'Fecha' => !empty($sourceData['search_info']['filters']['birthDate']) ? \Carbon\Carbon::parse($sourceData['search_info']['filters']['birthDate']) : now(),
                'Creado' => 'Sistema API',
                'Candidato' => Auth::id() ?? 1,
            ];
            
            $this->processAndPersistResults($sourceData, $busquedaData);
        } else {
            Log::warning('No se encontró información de CURP en la respuesta de la API para persistir.', ['curp' => $validatedData['curp']]);
        }

        return $responseData;
    }

    /**
     * Busca por nombre en la API externa y persiste los datos en la base de datos local.
     *
     * @param array $validatedData Datos validados del request (firstName, lastName, etc.).
     * @return array La respuesta completa de la API externa.
     * @throws ConnectionException|Throwable
     */
    public function searchAndPersistByName(array $validatedData): array
    {
        Log::info('Iniciando búsqueda por Nombre.', ['data' => $validatedData]);
        $endpoint = config('services.poder_judicial.base_uri') . '/search';
        Log::debug('Consumiendo endpoint de búsqueda por nombre.', ['endpoint' => $endpoint]);

        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->post($endpoint, $validatedData);

        $response->throw();
        $responseData = $response->json();
        Log::debug('Respuesta JSON cruda de la API recibida.', ['body' => $responseData]);
        
        // Para búsquedas por nombre, la respuesta de la API suele estar en el nivel raíz.
        $sourceData = $responseData; 
        Log::info("sourceData (byName) = " . json_encode($sourceData));

        $busquedaData = [
            'Nombres' => $validatedData['firstName'] ?? '',
            'Apellidos' => $validatedData['lastName'] ?? '',
            'CURP' => null, // No se proporciona CURP en este tipo de búsqueda.
            'Fecha' => !empty($validatedData['birthDate']) ? \Carbon\Carbon::parse($validatedData['birthDate']) : now(),
            'Creado' => 'Sistema API',
            'Candidato' => Auth::id() ?? 1,
        ];
        
        $this->processAndPersistResults($sourceData, $busquedaData);
        
        return $responseData;
    }

    /**
     * Procesa y persiste los resultados de una búsqueda (expedientes, partes, acuerdos).
     *
     * @param array $sourceData Los datos de la respuesta de la API que contienen los resultados.
     * @param array $busquedaData Los datos para crear el registro principal en Busqueda_RAL.
     * @return void
     * @throws \RuntimeException
     */
    private function processAndPersistResults(array $sourceData, array $busquedaData): void
    {
        $busquedaRal = null;
        try {
            Log::info("Datos extraídos para BusquedaRal (pre-insert): " . json_encode($busquedaData));
            $busquedaRal = BusquedaRal::create($busquedaData);
            if (!$busquedaRal->ID) {
                throw new \Exception("Fallo al obtener ID de Busqueda_RAL después de la inserción.");
            }
            Log::info("BusquedaRal creada exitosamente con ID: " . $busquedaRal->ID);
        } catch (Throwable $e) {
            Log::error("Fallo crítico al persistir BusquedaRal: " . $e->getMessage(), ['data' => $busquedaData]);
            throw new \RuntimeException("Error interno del servidor al procesar la búsqueda.");
        }

        $resultsExact = $sourceData['results_exact'] ?? [];
        $resultsExtended = $sourceData['results_extended'] ?? [];
        $resultsGeneral = $sourceData['results'] ?? [];
        $allResults = array_merge($resultsExact, $resultsExtended, $resultsGeneral);
        
        if (empty($allResults)) {
            Log::info('No se encontraron expedientes en la respuesta de la API para procesar.', ['busqueda_id' => $busquedaRal->ID]);
            return;
        }
        
        Log::info('Iniciando procesamiento de ' . count($allResults) . ' expedientes.', ['busqueda_id' => $busquedaRal->ID]);

        foreach ($allResults as $index => $resultado) {
            $objExp = $resultado['objExpediente'] ?? null;
            if (!$objExp) {
                Log::warning('Elemento de resultado sin objExpediente en el índice: ' . $index, ['busqueda_id' => $busquedaRal->ID]);
                continue;
            }

            Log::debug('Procesando expediente.', ['index' => $index, 'expediente_api_id' => $objExp['_id'] ?? 'N/A']);

            $expedienteRal = new ExpedienteRal();
            $expedienteRal->ID_Busqueda_RAL = $busquedaRal->ID;
            $expedienteRal->Fecha = $objExp['exp_fecha'] ?? null;
            $expedienteRal->Num_Expediente = $objExp['exp_num_expediente'] ?? '';
            $expedienteRal->Anio = $objExp['exp_anio'] ?? null;
            $expedienteRal->Estado = $objExp['exp_estado'] ?? '';
            $expedienteRal->Ciudad = $objExp['exp_ciudad'] ?? '';
            $expedienteRal->Juzgado = $objExp['exp_juzgado'] ?? '';
            $expedienteRal->Op = $objExp['exp_op'] ?? '';
            $expedienteRal->Toca = $objExp['exp_toca'] ?? '';
            $expedienteRal->Actor = $objExp['exp_actor'] ?? '';
            $expedienteRal->Demandado = $objExp['exp_demandado'] ?? '';
            $expedienteRal->Tipo = $objExp['exp_tipo'] ?? '';
            $expedienteRal->Materia = $objExp['materia'] ?? '';
            $expedienteRal->Fecha_ultima_actualizacion = $objExp['exp_lastmod'] ?? null;
            $expedienteRal->Rol_detectado = $objExp['exp_encontrado'] ?? null;
            $expedienteRal->save(); 

            $idExpedienteRal = $expedienteRal->ID;
            Log::info('Expediente guardado con ID: ' . $idExpedienteRal);

            $this->processExpedientePartes($objExp, $busquedaData['Nombres'], $busquedaData['Apellidos'], $idExpedienteRal);
            $this->processExpedienteAcuerdos($resultado['objAcuerdos'] ?? [], $idExpedienteRal);

            $scoreFinal = $this->calculateFinalScore(
                $busquedaData['Nombres'], 
                $busquedaData['Apellidos'], 
                $busquedaData['CURP'], 
                $objExp['exp_estado'] ?? '', 
                $idExpedienteRal
            );

            $expedienteRal->score = $scoreFinal;
            $expedienteRal->save();
            Log::debug('Score final calculado y guardado para el expediente.', ['expediente_id' => $idExpedienteRal, 'score' => $scoreFinal]);
        }
    }
    
    /**
     * Equivalencia Legacy: Lógica de PoderJudciaTareaProgramada.php (bucle $roles)
     */
    private function processExpedientePartes(array $objExp, string $candNombres, string $candApellidos, int $idExpedienteRal): void
    {
        Log::debug('Iniciando procesamiento de partes para el expediente.', ['expediente_id' => $idExpedienteRal]);
        $roles = [
            'actor' => RalUtilidades::split_partes($objExp['exp_actor'] ?? ''),
            'demandado' => RalUtilidades::split_partes($objExp['exp_demandado'] ?? '')
        ];
        
        $candNombreCompleto = trim($candNombres . ' ' . $candApellidos);

        foreach ($roles as $rol => $listaPartes) {
            Log::debug('Procesando ' . count($listaPartes) . ' partes para el rol: ' . $rol);
            foreach ($listaPartes as $parte) {
                $nombreParte = $parte['nombre'] ?? $parte; 
                
                $score = RalUtilidades::score_coincidencia($candNombreCompleto, [['nombre' => $nombreParte]]);
                $esCandidato = $score >= 80 ? 1 : 0;

                $parteRal = new ExpedienteRalParte();
                $parteRal->ID_Expediente = $idExpedienteRal;
                $parteRal->Rol = $rol;
                $parteRal->Nombre_parte = $nombreParte;
                $parteRal->Nombre_normalizado = RalUtilidades::norm_text($nombreParte);
                $parteRal->Tipo_parte = 'Persona';
                $parteRal->Es_Candidato = $esCandidato;
                $parteRal->score = $score;
                $parteRal->score_laboral = 0;

                $parteRal->save();
            }
        }
    }

    /**
     * Equivalencia Legacy: Lógica de PoderJudciaTareaProgramada.php (bucle $objAcuerdos)
     */
    private function processExpedienteAcuerdos(array $objAcuerdos, int $idExpedienteRal): void
    {
        Log::debug('Iniciando procesamiento de ' . count($objAcuerdos) . ' acuerdos para el expediente.', ['expediente_id' => $idExpedienteRal]);
        foreach ($objAcuerdos as $acuerdo) {
            $acuerdoRal = new AcuerdoRal();
            $acuerdoRal->ID_Expediente_RAL = $idExpedienteRal;

            $acuerdoRal->Fecha = $acuerdo['acu_fecha'] ?? null;
            $acuerdoRal->Acuerdo = $acuerdo['acu_acuerdo'] ?? '';
            $acuerdoRal->Tipo = $acuerdo['acu_tipo'] ?? '';
            $acuerdoRal->Actor = $acuerdo['acu_actor'] ?? '';
            $acuerdoRal->Demandado = $acuerdo['acu_demandado'] ?? '';
            $acuerdoRal->save();
        }
    }

    /**
     * Equivalencia Legacy: Cálculo al final del bucle foreach ($resultados as $resultado) en PoderJudciaTareaProgramada.php
     */
    private function calculateFinalScore(string $candNombres, string $candApellidos, ?string $curp, string $expEstado, int $idExpedienteRal): int
    {
        $scoreHomonimia = RalUtilidades::score_coincidencia(trim($candNombres . ' ' . $candApellidos), [['nombre' => 'DUMMY_PART_FOR_TEST']]) ?? 0;
        
        $encontroEstado = 0;
        if ($curp) {
            $estadoCurp = RalUtilidades::norm_estado(RalUtilidades::extract_estado($curp));
            $estadoExp = RalUtilidades::norm_estado($expEstado);
            $encontroEstado = ($estadoCurp === $estadoExp) ? 1 : 0;
        }
        
        $encontroIdentificador = 0;
        $maximoPorcentajeEmpleo = 0; 
        
        // FÓRMULA LEGACY SIMPLIFICADA (ajustada a los pesos conocidos):
        $scoreFinal = 0;
        $scoreFinal += ($scoreHomonimia * 0.60); 
        $scoreFinal += ($maximoPorcentajeEmpleo * 0.30);
        $scoreFinal += ($encontroEstado * 10);
        
        $scoreFinal = round($scoreFinal);

        if ($encontroIdentificador) {
            $scoreFinal = 100;
        }
        
        Log::debug('Cálculo de score final.', [
            'expediente_id' => $idExpedienteRal,
            'scoreHomonimia' => $scoreHomonimia,
            'encontroEstado' => $encontroEstado,
            'scoreFinal' => $scoreFinal
        ]);

        return $scoreFinal;
    }
}

