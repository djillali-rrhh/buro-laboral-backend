<?php

use App\Http\Controllers\Api\V1\PoderJudicialController;
use Illuminate\Support\Facades\Route;

// Rutas para el servicio de Poder Judicial API
// Documentación: https://www.poderjudicialvirtual.com/developers

Route::prefix('poder-judicial')->name('poderjudicial.')->group(function () {

    /**
     * AUTENTICACIÓN
     */
    Route::get('/account', [PoderJudicialController::class, 'account'])
        ->name('account');

    /**
     * BÚSQUEDA GENERAL DE PERSONAS
     */
    Route::get('/curp/{curp}', [PoderJudicialController::class, 'getCurp'])
        ->name('curp.get');

    Route::get('/search/{nombres}/{apellidos}', [PoderJudicialController::class, 'searchByNombresApellidos'])
        ->name('search.by_name_parts');

    Route::get('/search/{nombresCompleto}', [PoderJudicialController::class, 'searchByNombreCompleto'])
        ->name('search.by_fullname');

    Route::get('/exactsearch/{nombresCompleto}', [PoderJudicialController::class, 'exactSearchByNombreCompleto'])
        ->name('search.exact');

    Route::post('/curp', [PoderJudicialController::class, 'searchByCurpPost'])
        ->name('curp.post');

    Route::post('/search', [PoderJudicialController::class, 'searchByNamePost'])
        ->name('search.post');

    /**
     * 🚀 NUEVO ENDPOINT: BÚSQUEDA JUDICIAL COMPLETA CON PERSISTENCIA
     *
     * Endpoint moderno que integra:
     * - Búsqueda por CURP y Estado
     * - Llamada a API externa
     * - Almacenamiento completo en base de datos
     * - Cálculo de score
     * - Respuesta estructurada
     *
     * Ejemplo: POST /api/poder-judicial/search-db
     * Body: { "curp": "BACS970805HNLNRR01", "state": "NUEVO LEÓN" }
     */
    Route::post('/search-db', [PoderJudicialController::class, 'search'])
        ->name('search.db');

    /**
     * BÚSQUEDA DE EMPRESAS
     */
    Route::get('/company-search/{nombreEmpresa}', [PoderJudicialController::class, 'searchCompanyByName'])
        ->name('company.search.by_name');

    Route::get('/rfc/{claveRfc}', [PoderJudicialController::class, 'searchCompanyByRfc'])
        ->where('claveRfc', '^[A-ZÑ&]{3,4}\d{6}(?:[A-Z\d]{3})?$')
        ->name('company.search.by_rfc');

    /**
     * GENERACIÓN DE REPORTES
     */
    Route::get('/report-pdf/{searchId}', [PoderJudicialController::class, 'getReportPdf'])
        ->where('searchId', '^[a-f0-9]{32}$')
        ->name('report.pdf.get');

    /**
     * CONSULTA DE CÉDULAS PROFESIONALES
     */
    Route::get('/cedula/{cedula}', [PoderJudicialController::class, 'getCedulaByNumero'])
        ->where('cedula', '^[0-9]+$')
        ->name('cedula.get.by_number');

    Route::get('/cedula-nombre/{nombres}/{apellido1}/{apellido2}', [PoderJudicialController::class, 'getCedulaByNombre'])
        ->name('cedula.get.by_name');
});
