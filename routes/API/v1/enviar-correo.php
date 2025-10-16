<?php

use App\Http\Controllers\Api\V1\EmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('email')->group(function () {
    Route::post('/queue', [EmailController::class, 'queue']);
});
