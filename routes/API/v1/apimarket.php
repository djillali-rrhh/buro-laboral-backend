<?php

/**
 * Archivo de rutas para la integración con ApiMarket.
 *
 * Define los endpoints de la API para obtener el NSS y la trayectoria laboral
 * de un candidato, agrupados bajo el prefijo '/apimarket'.
 *
 * @package App\Routes
 * @version 1.0.0
 */

use App\Http\Controllers\Api\V1\ApiMarketController;
use Illuminate\Support\Facades\Route;

Route::prefix('apimarket')->group(function () {
    /**
     * Endpoint para obtener el NSS de un candidato a partir de su CURP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    Route::post('/nss', [ApiMarketController::class, 'obtenerNSS']);

    /**
     * Endpoint para obtener la trayectoria laboral de un candidato.
     * La consulta se puede realizar utilizando el NSS o la CURP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    Route::post('/trayectoria', [ApiMarketController::class, 'obtenerTrayectoria']);
});

