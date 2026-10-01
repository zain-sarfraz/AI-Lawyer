<?php

namespace App\Providers;

use App\Auth\FirmAwareUserProvider;
use App\Domain\Firm\FirmContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirmContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('firm-eloquent', function ($app, array $config) {
            return new FirmAwareUserProvider($app['hash'], $config['model']);
        });
    }
}
