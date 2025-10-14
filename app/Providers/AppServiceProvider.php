<?php

namespace App\Providers;

use App\Services\BuroDeIngresos\BuroDeIngresosService;
use App\Services\BuroDeIngresos\Actions\RetryAction;
use App\Services\BuroDeIngresos\Actions\ProcessCandidatoDatos;
use App\Services\BuroDeIngresos\Actions\ProcessCandidatoDatosExtra;
use App\Services\BuroDeIngresos\Actions\ProcessCandidatoLaborales;
use App\Services\BuroDeIngresos\Actions\ProcessDocumentosSA;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}