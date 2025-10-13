<?php

use Illuminate\Support\Facades\Route;

/**
 * Carga dinámica de archivos de rutas dentro de /routes/API/v1.
 */
Route::prefix('v1')->group(function () {
    $path = base_path('routes/API/v1');

    foreach (glob("$path/*.php") as $routeFile) {
        require $routeFile;
    }
});