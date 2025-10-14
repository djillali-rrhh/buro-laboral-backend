<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\IngeniaApiController;

Route::prefix('ingenia')->group(function () {
    Route::post('/consulta-curp', [IngeniaApiController::class, 'consultarPorCurp']);
    Route::post('/obtener-curp', [IngeniaApiController::class, 'obtenerCurpPorDatos']);
});