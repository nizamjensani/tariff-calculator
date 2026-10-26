<?php

namespace App\Providers;

use App\Services\TariffCalculator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            TariffCalculator::class,
            fn ($app) => new TariffCalculator($app['config']->get('tariff', [])),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
