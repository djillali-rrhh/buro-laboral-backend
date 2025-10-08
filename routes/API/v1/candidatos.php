<?php

use App\Http\Controllers\Api\CandidatoController;
use Illuminate\Support\Facades\Route;

Route::get('/candidatos', [CandidatoController::class, 'index']);
Route::get('/candidatos/{candidato}', [CandidatoController::class, 'show']);