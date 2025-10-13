<?php

use App\Http\Controllers\Api\V1\PoderJudicialController;
use Illuminate\Support\Facades\Route;

// Rutas para el servicio de Poder Judicial API
// Documentación: https://www.poderjudicialvirtual.com/developers

// API ENDPOINTS
# Autenticación
// account              GET /account

# Personas
// get-curp           GET /curp/{curp}
// search-by-names    GET /search/{nombres}/{apellidos}
// search-by-fullname GET /search/{nombresCompleto}
// exact-search       GET /exactsearch/{nombresCompleto}
// post-curp          POST /curp
// post-search        POST /search

# Empresas
// search-company     GET /company-search/{nombreEmpresa}
// search-rfc         GET /rfc/{claveRfc}

# Reportes
// get-report-pdf     GET /report-pdf/{searchId}

# Cédulas Profesionales
// get-cedula-by-num  GET /cedula/{cedula}
// get-cedula-by-name GET /cedula-nombre/{nombres}/{apellido1}/{apellido2}


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