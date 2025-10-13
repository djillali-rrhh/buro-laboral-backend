<?php

use App\Http\Controllers\Api\V1\CandidatoController;
use Illuminate\Support\Facades\Route;

Route::get('/candidatos', [CandidatoController::class, 'index']);
Route::get('/candidatos/{candidato}', [CandidatoController::class, 'show']);