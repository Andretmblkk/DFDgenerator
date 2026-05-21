<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\LaravelDfdServiceProvider;
use LaravelDfd\Scanner\RouteScanner;
use LaravelDfd\Tests\TestCase;

final class RouteScannerTest extends TestCase
{
    public function test_it_scans_registered_laravel_routes(): void
    {
        Route::get('users/{user}', TestController::class . '@show');
        Route::post('posts', [TestController::class, 'store']);
        Route::get('health', static fn (): string => 'ok');

        $routes = (new RouteScanner())->scan();

        self::assertContains([
            'uri' => 'users/{user}',
            'methods' => ['GET', 'HEAD'],
            'action' => TestController::class . '@show',
        ], $routes);

        self::assertContains([
            'uri' => 'posts',
            'methods' => ['POST'],
            'action' => TestController::class . '@store',
        ], $routes);

        self::assertContains([
            'uri' => 'health',
            'methods' => ['GET', 'HEAD'],
            'action' => 'Closure',
        ], $routes);
    }

    public function test_package_service_provider_is_loaded(): void
    {
        self::assertTrue($this->app->providerIsLoaded(LaravelDfdServiceProvider::class));
        self::assertIsString(config('dfd.output_path'));
    }
}

final class TestController
{
    public function show(): void
    {
    }

    public function store(): void
    {
    }
}
