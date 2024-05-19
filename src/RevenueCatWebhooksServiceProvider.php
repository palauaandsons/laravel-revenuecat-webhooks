<?php

namespace PalauaAndSons\RevenueCatWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RevenueCatWebhooksServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/revenuecat-webhooks.php' => config_path('revenuecat-webhooks.php'),
            ], 'config');
        }

        Route::macro('revenueCatWebhooks', fn ($url) => Route::post($url, '\PalauaAndSons\RevenueCatWebhooks\RevenueCatWebhooksController'));
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/revenuecat-webhooks.php', 'revenuecat-webhooks');
    }
}
