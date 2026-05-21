<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\Builder\HierarchyBuilder;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\Tests\TestCase;

final class HierarchyBuilderTest extends TestCase
{
    public function test_it_builds_formal_hierarchical_levels_with_semantic_groups_and_numbering(): void
    {
        config()->set('laravel-dfd.system_name', 'Futsal Booking System');
        config()->set('laravel-dfd.groups', [
            'booking' => [
                'label' => 'Booking Management',
                'controllers' => ['HierarchyBookingController', 'HierarchyScheduleController'],
            ],
            'payment' => [
                'label' => 'Payment Processing',
                'controllers' => ['HierarchyPaymentController'],
            ],
        ]);

        Route::post('bookings', HierarchyBookingController::class . '@store');
        Route::get('schedules', HierarchyScheduleController::class . '@index');
        Route::post('payments', HierarchyPaymentController::class . '@store');

        $hierarchy = (new HierarchyBuilder())->build(3);

        self::assertSame('Futsal Booking System', $hierarchy['system']);
        self::assertGreaterThanOrEqual(4, count($hierarchy['levels']));

        $level0 = $hierarchy['levels'][0];
        self::assertInstanceOf(DFDLevel::class, $level0);
        self::assertSame(0, $level0->getLevel());
        self::assertSame('0 Futsal Booking System', $level0->getProcesses()[0]->getLabel());

        $level1 = $hierarchy['levels'][1];
        self::assertContains('1.0 Pemrosesan Pembayaran', array_map(
            static fn ($process): string => $process->getLabel(),
            $level1->getProcesses(),
        ));

        $bookingLevel2 = $hierarchy['levels'][2];
        self::assertSame(2, $bookingLevel2->getLevel());
        self::assertNotEmpty($bookingLevel2->getProcesses());

        $level3 = array_values(array_filter(
            $hierarchy['levels'],
            static fn (DFDLevel $level): bool => $level->getLevel() === 3,
        ));
        self::assertNotEmpty($level3);
        self::assertStringContainsString('Pembayaran', $level3[0]->getTitle());
    }

    public function test_artisan_generation_writes_all_level_outputs_and_viewer_assets(): void
    {
        Route::post('hierarchy-command-bookings', HierarchyBookingController::class . '@store');

        $this->artisan('dfd:generate')->assertSuccessful();

        self::assertFileExists(storage_path('dfd/level-0.json'));
        self::assertFileExists(storage_path('dfd/level-1.json'));
        self::assertFileExists(storage_path('dfd/level-2.json'));
        self::assertFileExists(storage_path('dfd/level-3.json'));
        self::assertFileExists(storage_path('dfd/level-0.svg'));
        self::assertFileExists(storage_path('dfd/level-1.svg'));
        self::assertFileExists(storage_path('dfd/level-2.svg'));
        self::assertFileExists(storage_path('dfd/level-3.svg'));
        self::assertFileExists(storage_path('dfd/index.html'));
        self::assertFileExists(storage_path('dfd/assets/styles.css'));
        self::assertFileExists(storage_path('dfd/assets/viewer.js'));

        $json = json_decode((string) file_get_contents(storage_path('dfd/level-2.json')), true);
        self::assertIsArray($json);
        self::assertSame(2, $json['level']);
        self::assertArrayHasKey('diagrams', $json);
        self::assertStringContainsString('<svg', (string) file_get_contents(storage_path('dfd/level-2.svg')));
        self::assertStringContainsString('id="levelNav"', (string) file_get_contents(storage_path('dfd/index.html')));
    }
}

final class HierarchyBookingController
{
    public function store(): void
    {
        Booking::create(['date' => '2026-05-18']);
        DB::table('bookings')->insert(['date' => '2026-05-18']);
    }
}

final class HierarchyScheduleController
{
    public function index(): void
    {
        Schedule::where('active', true)->get();
    }
}

final class HierarchyPaymentController
{
    public function store(): void
    {
        Payment::create(['amount' => 100]);
    }
}
