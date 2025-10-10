<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PoderJudicialController;

Route::get('/poderjudicial/login', [PoderJudicialController::class, 'login']);
Route::get('/poderjudicial/curp/{curp}', [PoderJudicialController::class, 'getCurp']);

Route::get('/poderjudicial/search/{nombres}/{apellidos}', [PoderJudicialController::class, 'searchByNombresApellidos']);
Route::get('/poderjudicial/search/{nombresCompleto}', [PoderJudicialController::class, 'searchByNombreCompleto']);
Route::get('/poderjudicial/exactsearch/{nombresCompleto}', [PoderJudicialController::class, 'exactSearchByNombreCompleto']);

Route::post('/poderjudicial/curp', [PoderJudicialController::class, 'searchByCurpPost']);
Route::post('/poderjudicial/search', [PoderJudicialController::class, 'searchByNamePost']);