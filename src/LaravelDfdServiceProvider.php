<?php

declare(strict_types=1);

namespace LaravelDfd;

use Illuminate\Support\ServiceProvider;
use LaravelDfd\Commands\DfdCommand;

final class LaravelDfdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-dfd.php', 'laravel-dfd');
        $this->mergeConfigFrom(__DIR__ . '/../config/dfd.php', 'dfd');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-dfd');

        $this->publishes([
            __DIR__ . '/../config/laravel-dfd.php' => config_path('laravel-dfd.php'),
            __DIR__ . '/../config/dfd.php' => config_path('dfd.php'),
        ], 'dfd-config');

        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/dfdgenerator'),
        ], 'dfd-assets');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DfdCommand::class,
            ]);
        }
    }
}
