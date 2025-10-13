<?php

use App\Http\Controllers\Api\V1\SaludoController;
use Illuminate\Support\Facades\Route;

Route::get('/saludo', SaludoController::class);
