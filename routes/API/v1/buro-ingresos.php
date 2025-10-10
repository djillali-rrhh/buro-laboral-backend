<?php

use App\Http\Controllers\Api\BuroDeIngresosApiController;
use Illuminate\Support\Facades\Route;

// Rutas para Buró de Ingresos API

// API ORIGINAL
# consents
// create-consent       https://api.burodeingresos.com/consents
// create-bulk-consents https://api.burodeingresos.com/consents/bulk
// list-consents        https://api.burodeingresos.com/consents

# verifications
// create-verification  https://api.burodeingresos.com/verifications
// list-verifications   https://api.burodeingresos.com/verifications
// create-bulk-verifications https://api.burodeingresos.com/verifications/bulk
// get-verification https://api.burodeingresos.com/verifications/{verification_id}
// get-bulk-verification-status https://api.burodeingresos.com/verifications/bulk/{bulk_id}
// delete-verification https://api.burodeingresos.com/verifications/{verification_id}
// delete-bulk-verification https://api.burodeingresos.com/verifications/bulk/{bulk_id}

# information
// get-profile https://api.burodeingresos.com/profile/{identifier}
// get-employments https://api.burodeingresos.com/employments/{identifier}
// get-invoices https://api.burodeingresos.com/invoices/{identifier}

# webhooks
// create-webhook https://api.burodeingresos.com/webhooks
// get-webhook https://api.burodeingresos.com/webhooks/{webhook_id}
// delete-webhook https://api.burodeingresos.com/webhooks/{webhook_id}
// update-webhook https://api.burodeingresos.com/webhooks/{webhook_id}
// list-webhooks https://api.burodeingresos.com/webhooks


Route::prefix('buro-ingresos')->name('buro.')->group(function () {

    /**
     * CONSENTIMIENTOS
     */
    Route::post('/consents', [BuroDeIngresosApiController::class, 'createConsent'])
        ->name('consents.create');

    Route::post('/consents/bulk', [BuroDeIngresosApiController::class, 'createBulkConsents'])
        ->name('consents.create.bulk');

    Route::get('/consents', [BuroDeIngresosApiController::class, 'listConsents'])
        ->name('consents.list');

    /**
     * VERIFICACIONES
     */
    Route::post('/verifications', [BuroDeIngresosApiController::class, 'createVerification'])
        ->name('verifications.create');

    Route::get('/verifications', [BuroDeIngresosApiController::class, 'listVerifications'])
        ->name('verifications.list');

    Route::post('/verifications/bulk', [BuroDeIngresosApiController::class, 'createBulkVerifications'])
        ->name('verifications.create.bulk');

    Route::get('/verifications/{verificationId}', [BuroDeIngresosApiController::class, 'getVerification'])
        ->name('verifications.get');

    Route::get('/verifications/bulk/{bulkId}', [BuroDeIngresosApiController::class, 'getBulkVerificationStatus'])
        ->name('verifications.bulk.status');

    Route::delete('/verifications/{verificationId}', [BuroDeIngresosApiController::class, 'deleteVerification'])
        ->name('verifications.delete');

    Route::delete('/verifications/bulk/{bulkId}', [BuroDeIngresosApiController::class, 'deleteBulkVerification'])
        ->name('verifications.bulk.delete');

    /**
     * INFORMACIÓN
     */
    Route::get('/profile/{identifier}', [BuroDeIngresosApiController::class, 'getProfile'])
        ->name('profile.get');

    Route::get('/employments/{identifier}', [BuroDeIngresosApiController::class, 'getEmployments'])
        ->name('employments.get');

    Route::get('/invoices/{identifier}', [BuroDeIngresosApiController::class, 'getInvoices'])
        ->name('invoices.get');

    // Método auxiliar que obtiene los datos completos (perfil, empleos e invoices) del candidato
    Route::get('/data/{identifier}', [BuroDeIngresosApiController::class, 'getCandidateData'])
        ->name('candidate.data');

    /**
     * WEBHOOKS
     */
    Route::post('/webhooks', [BuroDeIngresosApiController::class, 'createWebhook'])
        ->name('webhooks.create');

    Route::get('/webhooks', [BuroDeIngresosApiController::class, 'listWebhooks'])
        ->name('webhooks.list');

    Route::get('/webhooks/{webhookId}', [BuroDeIngresosApiController::class, 'getWebhook'])
        ->name('webhooks.get');

    Route::delete('/webhooks/{webhookId}', [BuroDeIngresosApiController::class, 'deleteWebhook'])
        ->name('webhooks.delete');

    Route::patch('/webhooks/{webhookId}', [BuroDeIngresosApiController::class, 'updateWebhook'])
        ->name('webhooks.update');
});
