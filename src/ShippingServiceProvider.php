<?php

namespace ItmoSoft\Shipping;

use Illuminate\Support\ServiceProvider;
use ItmoSoft\Shipping\Services\ShippingQuoteService;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/itmosoft-shipping.php', 'itmosoft-shipping');
        $this->app->singleton(ShippingQuoteService::class);
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/itmosoft-shipping.php' => config_path('itmosoft-shipping.php')], 'itmosoft-shipping-config');
        $this->publishes([__DIR__.'/../database/migrations/' => database_path('migrations')], 'itmosoft-shipping-migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
