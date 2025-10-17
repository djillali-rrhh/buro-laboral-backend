<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\WhatsAppController;
use App\Http\Controllers\Api\V1\WhatsAppTemplateController;

Route::prefix('whatsapp')->group(function () {
    
    Route::match(['get', 'post'], 'webhook', [WhatsAppController::class, 'receiveWebhook'])
        ->name('whatsapp.webhook'); 

    Route::post('send-template', [WhatsAppController::class, 'sendTemplate'])
        ->name('whatsapp.send-template');

    Route::post('send-template-batch', [WhatsAppController::class, 'sendTemplateBatch'])
        ->name('whatsapp.send-template-batch');
    
    Route::post('send-text', [WhatsAppController::class, 'sendText'])
        ->name('whatsapp.send-text');
    
    Route::get('templates-from-meta', [WhatsAppController::class, 'getTemplates'])
        ->name('whatsapp.templates-from-meta');

    Route::prefix('templates')->name('whatsapp.templates.')->group(function () {
        Route::get('/', [WhatsAppTemplateController::class, 'index'])->name('index');
        Route::post('/', [WhatsAppTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [WhatsAppTemplateController::class, 'show'])->name('show');
        Route::delete('/{template}', [WhatsAppTemplateController::class, 'destroy'])->name('destroy');
    });
});
