<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EmailService\EmailService;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EmailService::class, function ($app) {
            $strategy = config('email-service.driver', env('MAIL_EMAIL_SERVICE_DRIVER', 'smtp'));
            return new EmailService($strategy);
        });
    }

    public function boot(): void
    {
        //
    }
}