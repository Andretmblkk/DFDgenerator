<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LaravelDfd\Http\Controllers\DfdController;

if ((bool) config('laravel-dfd.route.enabled', true)) {
    $prefix = trim((string) config('laravel-dfd.route.prefix', 'dfd'), '/');
    $middleware = config('laravel-dfd.route.middleware', ['web']);

    Route::middleware($middleware)
        ->prefix($prefix)
        ->group(static function (): void {
            Route::get('/', [DfdController::class, 'show'])->name('laravel-dfd.viewer');
            Route::get('/assets/styles.css', [DfdController::class, 'styles'])->name('laravel-dfd.assets.styles');
            Route::get('/assets/viewer.js', [DfdController::class, 'script'])->name('laravel-dfd.assets.script');
        });
}
