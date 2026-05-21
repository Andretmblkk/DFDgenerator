<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\Tests\TestCase;

final class DfdWebRouteTest extends TestCase
{
    public function test_dfd_viewer_is_available_from_configured_web_route(): void
    {
        Route::post('web-route-users', DfdWebRouteFixtureController::class . '@store');

        $response = $this->get('/dfd');

        $response->assertOk();
        $response->assertSee('Laravel DFD Generator', false);
        $response->assertSee('window.DFD_DATA', false);
        $response->assertSee('assets/viewer.js', false);
    }

    public function test_dfd_assets_are_served_by_package_routes(): void
    {
        $this->get('/dfd/assets/styles.css')
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');

        $this->get('/dfd/assets/viewer.js')
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript; charset=UTF-8');
    }

    public function test_dfd_route_can_be_disabled_from_config(): void
    {
        config(['laravel-dfd.route.enabled' => false]);

        $this->get('/dfd')->assertNotFound();
    }
}

final class DfdWebRouteFixtureController
{
    public function store(): void
    {
        DfdWebRouteUser::create(['name' => 'A']);
    }
}
