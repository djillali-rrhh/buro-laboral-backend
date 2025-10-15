<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\IngeniaApiController;

/*
|--------------------------------------------------------------------------
| Rutas de la API de Ingenia
|--------------------------------------------------------------------------
|
| Este archivo define los endpoints para la integración con la API de Ingenia.
| Todas las rutas definidas aquí están agrupadas bajo el prefijo '/api/v1/ingenia'.
|
*/

Route::prefix('ingenia')->group(function () {
    /**
     * @route   POST /api/v1/ingenia/consulta-curp
     * @desc    Endpoint para obtener la información de un candidato a partir de su CURP.
     * @access  public
     */
    Route::post('/consulta-curp', [IngeniaApiController::class, 'consultarPorCurp']);

    /**
     * @route   POST /api/v1/ingenia/obtener-curp
     * @desc    Endpoint para obtener la CURP de un candidato a partir de sus datos personales.
     * @access  public
     */
    Route::post('/obtener-curp', [IngeniaApiController::class, 'obtenerCurpPorDatos']);
});
