<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PoderJudicialController;

// -- Rutas para el servicio de Poder Judicial --
Route::prefix('poder-judicial')->controller(PoderJudicialController::class)->group(function () {
    
    // -- Autenticación y Cuentas --
    Route::get('/login', 'login');
    
    // -- Búsqueda General de Personas --
    Route::get('/curp/{curp}', 'getCurp');
    Route::get('/search/{nombres}/{apellidos}', 'searchByNombresApellidos');
    Route::get('/search/{nombresCompleto}', 'searchByNombreCompleto');
    Route::get('/exactsearch/{nombresCompleto}', 'exactSearchByNombreCompleto');
    Route::post('/curp', 'searchByCurpPost');
    Route::post('/search', 'searchByNamePost');

    // -- Búsqueda de Empresas --
    Route::get('/company-search/{nombreEmpresa}', 'searchCompanyByName');
    Route::get('/rfc/{claveRfc}', 'searchCompanyByRfc')
        ->where('claveRfc', '^[A-ZÑ&]{3,4}\d{6}(?:[A-Z\d]{3})?$');

    // -- Generación de Reportes --
    Route::get('/report-pdf/{searchId}', 'getReportPdf')
        ->where('searchId', '^[a-f0-9]{32}$');

    // -- Consulta de Cédulas Profesionales --
    Route::get('/cedula/{cedula}', 'getCedulaByNumero')
        ->where('cedula', '^[0-9]+$');
    Route::get('/cedula-nombre/{nombres}/{apellido1}/{apellido2}', 'getCedulaByNombre');
});

