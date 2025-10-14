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
 * Equivalencia Legacy: Funcionalidad replicada de la clase PoderJudicialVirtual.php
 * y el flujo de guardado de PoderJudciaTareaProgramada.php.
 */
class PoderJudicialService
{
    /**
     * Equivalencia Legacy: Método buscarAuto, searchByCURP y flujo de persistencia.
     */
    public function searchAndPersistByCurp(array $validatedData): array
    {
        $response = Http::withHeaders([
            'apikey' => config('services.poder_judicial.secret'),
        ])->post(config('services.poder_judicial.base_uri') . '/curp', $validatedData);

        $response->throw();

        $responseData = $response->json();
        
        $sourceData = [];
        if (isset($responseData['data'])) {
            $sourceData = $responseData['data'];
        } elseif (isset($responseData['errors'])) {
            $sourceData = $responseData['errors'];
        } elseif (isset($responseData['curp_info'])) {
            $sourceData = $responseData; 
        }

        $isSuccessfulApiCall = $responseData['status'] ?? true;
        
        $curpInfo = $sourceData['curp_info'] ?? [];

        Log::info("sourceData = " . json_encode($sourceData));
        
        $resultsExact = $sourceData['results_exact'] ?? [];
        $resultsExtended = $sourceData['results_extended'] ?? [];
        $resultsGeneral = $sourceData['results'] ?? [];
        
        $allResults = array_merge($resultsExact, $resultsExtended, $resultsGeneral);
        $hasExpedientsToProcess = !empty($allResults);

        if (!$isSuccessfulApiCall && $hasExpedientsToProcess) {
            $isSuccessfulApiCall = true;
            Log::info("API_RAL: Status de negocio forzado a TRUE. Expedientes encontrados en respuesta de 'errors'.");
        }

        $busquedaRal = null;
        
        if (!empty($curpInfo)) {
            $nombres = $curpInfo['name'] ?? '';
            $apellidoPaterno = $curpInfo['firstlastname'] ?? '';
            $apellidoMaterno = $curpInfo['secondlastname'] ?? '';
            $apellidos = trim($apellidoPaterno . ' ' . $apellidoMaterno);
            
            $curp = $curpInfo['curp'] ?? $validatedData['curp'];

            $birthDate = $sourceData['search_info']['filters']['birthDate'] ?? null;
            $fecha = $birthDate ? \Carbon\Carbon::parse($birthDate) : now();

            $busquedaData = [
                'Nombres' => $nombres,
                'Apellidos' => $apellidos,
                'CURP' => $curp,
                'Fecha' => $fecha,
                'Creado' => 'Sistema API',
                'Candidato' => Auth::id() ?? 1,
            ];
            
            try {
                Log::info("Datos extraídos para BusquedaRal (pre-insert): " . json_encode($busquedaData));

                $busquedaRal = BusquedaRal::create($busquedaData);

                if (!$busquedaRal->ID) {
                    throw new \Exception("Fallo al obtener ID de Busqueda_RAL después de la inserción.");
                }
                Log::info("BusquedaRal creada exitosamente con ID: " . $busquedaRal->ID);

            } catch (Throwable $e) {
                Log::error("Fallo crítico al persistir BusquedaRal (CURP): " . $e->getMessage());
                throw new \Exception("Error interno del servidor al procesar la búsqueda.");
            }
        }
        
        if ($isSuccessfulApiCall && $busquedaRal) {
            
            foreach ($allResults as $resultado) {
                // Equivalencia Legacy: $expediente = $resultado->objExpediente;
                $objExp = $resultado['objExpediente'] ?? null;

                if (!$objExp) continue;

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

                $this->processExpedientePartes($objExp, $busquedaData['Nombres'], $busquedaData['Apellidos'], $idExpedienteRal);
                $this->processExpedienteAcuerdos($resultado['objAcuerdos'] ?? [], $idExpedienteRal);
                
                // CALCULAR Y ACTUALIZAR SCORE FINAL (Equivalencia Legacy)
                $scoreFinal = $this->calculateFinalScore(
                    $busquedaData['Nombres'], 
                    $busquedaData['Apellidos'], 
                    $curp, 
                    $objExp['exp_estado'] ?? '', 
                    $idExpedienteRal
                );

                $expedienteRal->score = $scoreFinal;
                $expedienteRal->save();
            }
            
            return $responseData;
        }

        return $responseData;
    }

    /**
     * Equivalencia Legacy: Lógica de PoderJudciaTareaProgramada.php (bucle $roles)
     */
    private function processExpedientePartes(array $objExp, string $candNombres, string $candApellidos, int $idExpedienteRal): void
    {
        $roles = [
            'actor' => RalUtilidades::split_partes($objExp['exp_actor'] ?? ''),
            'demandado' => RalUtilidades::split_partes($objExp['exp_demandado'] ?? '')
        ];
        
        $candNombreCompleto = trim($candNombres . ' ' . $candApellidos);

        foreach ($roles as $rol => $listaPartes) {
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
    private function calculateFinalScore(string $candNombres, string $candApellidos, string $curp, string $expEstado, int $idExpedienteRal): int
    {
        $scoreHomonimia = RalUtilidades::score_coincidencia(trim($candNombres . ' ' . $candApellidos), [['nombre' => 'DUMMY_PART_FOR_TEST']]) ?? 0;
        
        $estadoCurp = RalUtilidades::norm_estado(RalUtilidades::extract_estado($curp));
        $estadoExp = RalUtilidades::norm_estado($expEstado);

        $encontroEstado = ($estadoCurp === $estadoExp) ? 1 : 0;
        
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

        return $scoreFinal;
    }
}