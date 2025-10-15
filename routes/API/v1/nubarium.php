<?php

use App\Http\Controllers\Api\V1\NubariumController;
use Illuminate\Support\Facades\Route;

Route::prefix('nubarium')->name('nubarium.')->group(function () {
    Route::post('/curp/validar-curp', [NubariumController::class, 'validarCurp'])
        ->name('curp.validar');

    Route::post('/curp/obtener-curp', [NubariumController::class, 'obtenerCurp'])
        ->name('curp.obtener');
});
