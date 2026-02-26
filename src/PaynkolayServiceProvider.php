<?php

namespace Zkriahac\Paynkolay;

use Illuminate\Support\ServiceProvider;

class PaynkolayServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/Config/paynkolay.php', 'paynkolay'
        );

        // Register main service
        $this->app->singleton('paynkolay', function ($app) {
            return new PaynkolayService(
                config('paynkolay.merchant_id'),
                config('paynkolay.merchant_secret'),
                config('paynkolay.sx'),
                config('paynkolay.sx_cancel'),
                config('paynkolay.sx_list'),
                config('paynkolay.environment', 'sandbox'),
                config('paynkolay.urls', []),
                config('paynkolay.callback_urls', [])
                
            );
        });
        // Register aliases
        $this->app->alias('paynkolay', PaynkolayService::class);
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/Config/paynkolay.php' => config_path('paynkolay.php'),
        ], 'paynkolay-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'paynkolay-migrations');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/paynkolay'),
        ], 'paynkolay-views');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'paynkolay');
    }
}