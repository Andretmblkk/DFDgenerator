<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\Tests\TestCase;

final class DfdCommandTest extends TestCase
{
    public function test_it_generates_complete_dfd_suite_by_default(): void
    {
        Route::post('command-users', DfdCommandFixtureController::class . '@store');

        $this->artisan('dfd:generate')
            ->assertSuccessful();

        foreach ([0, 1, 2, 3] as $level) {
            self::assertFileExists(storage_path('dfd/level-' . $level . '.json'));
            self::assertFileExists(storage_path('dfd/level-' . $level . '.svg'));
        }

        self::assertFileExists(storage_path('dfd/index.html'));
        self::assertFileExists(storage_path('dfd/assets/styles.css'));
        self::assertFileExists(storage_path('dfd/assets/viewer.js'));

        $level1 = json_decode((string) file_get_contents(storage_path('dfd/level-1.json')), true);
        self::assertIsArray($level1);
        self::assertSame(1, $level1['level']);
        self::assertArrayHasKey('diagrams', $level1);

        self::assertStringContainsString('<svg', (string) file_get_contents(storage_path('dfd/level-0.svg')));
        self::assertStringContainsString('assets/viewer.js', (string) file_get_contents(storage_path('dfd/index.html')));
    }

    public function test_it_can_still_generate_legacy_mermaid_output_explicitly(): void
    {
        Route::post('command-users', DfdCommandFixtureController::class . '@store');

        $path = storage_path('dfd/diagram.mmd');

        $this->artisan('dfd:generate', [
            '--format' => 'mermaid',
            '--output' => $path,
        ])->assertSuccessful();

        self::assertFileExists($path);

        $contents = (string) file_get_contents($path);

        self::assertStringStartsWith('flowchart TD' . PHP_EOL, $contents);
        self::assertStringContainsString('external_client[User]', $contents);
        self::assertStringContainsString('store_user[(D1 Users)]', $contents);
        self::assertStringContainsString('store_command_user[(D2 Command Users)]', $contents);
    }

    public function test_it_generates_legacy_json_ir_when_json_option_is_provided_with_mermaid_format(): void
    {
        Route::post('command-users', DfdCommandFixtureController::class . '@store');

        $path = storage_path('dfd/command-ir.json');

        $this->artisan('dfd:generate', [
            '--format' => 'mermaid',
            '--json' => true,
            '--output' => $path,
        ])->assertSuccessful();

        self::assertFileExists($path);

        $json = json_decode((string) file_get_contents($path), true);

        self::assertIsArray($json);
        self::assertArrayHasKey('processes', $json);
        self::assertArrayHasKey('dataStores', $json);
        self::assertArrayHasKey('externalEntities', $json);
        self::assertArrayHasKey('flows', $json);
        self::assertContains([
            'id' => 'store.user',
            'name' => 'D1 Users',
            'type' => 'model',
        ], $json['dataStores']);
    }

    public function test_suite_generation_rejects_file_output_path(): void
    {
        $this->artisan('dfd:generate', [
            '--output' => storage_path('dfd/invalid.html'),
        ])->assertFailed();
    }
}

final class DfdCommandFixtureController
{
    public function store(): void
    {
        User::create(['name' => 'A']);
        DB::table('command_users')->insert(['name' => 'A']);
    }
}
