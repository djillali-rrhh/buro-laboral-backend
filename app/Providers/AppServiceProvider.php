<?php

namespace App\Providers;

use App\Services\BuroDeIngresos\BuroDeIngresosService;
use App\Services\BuroDeIngresos\BuroDeIngresosWebhookService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BuroDeIngresosService::class);
        $this->app->singleton(BuroDeIngresosWebhookService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}