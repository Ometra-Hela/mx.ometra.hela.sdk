<?php

namespace Ometra\HelaSdk;

use Illuminate\Support\ServiceProvider;

class HelaSdkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/hela-sdk.php', 'hela-sdk');

        $this->app->singleton(HelaSdk::class, function () {
            return new HelaSdk((array) config('hela-sdk', []));
        });

        $this->app->alias(HelaSdk::class, 'hela-sdk');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/hela-sdk.php' => config_path('hela-sdk.php'),
        ], 'hela-sdk-config');
    }
}
