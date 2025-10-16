<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\WhatsAppController;

Route::prefix('whatsapp')->group(function () {
    Route::post('send-template', [WhatsAppController::class, 'sendTemplate']);
    Route::post('send-text', [WhatsAppController::class, 'sendText']);
});

Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'receiveWebhook']);