<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\Builder\DFDBuilder;
use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Tests\TestCase;

final class DFDBuilderTest extends TestCase
{
    public function test_it_builds_ir_from_routes_controller_ast_models_and_tables(): void
    {
        Route::post('users', DFDBuilderFixtureController::class . '@store');
        Route::put('users/{user}', DFDBuilderFixtureController::class . '@update');

        $ir = (new DFDBuilder())->build();

        self::assertContainsOnlyInstancesOf(ProcessNode::class, $ir['processes']);
        self::assertContainsOnlyInstancesOf(DataStoreNode::class, $ir['dataStores']);
        self::assertContainsOnlyInstancesOf(ExternalEntityNode::class, $ir['externalEntities']);
        self::assertContainsOnlyInstancesOf(DataFlow::class, $ir['flows']);

        self::assertSame([
            'process.LaravelDfd.Tests.Unit.DFDBuilderFixtureController.store',
            'process.LaravelDfd.Tests.Unit.DFDBuilderFixtureController.update',
        ], array_map(static fn (ProcessNode $node): string => $node->getId(), $ir['processes']));

        self::assertSame([
            'store.user',
        ], array_map(static fn (DataStoreNode $node): string => $node->getId(), $ir['dataStores']));

        self::assertSame([
            'external.client',
        ], array_map(static fn (ExternalEntityNode $node): string => $node->getId(), $ir['externalEntities']));

        self::assertContains('POST users', $ir['processes'][0]->getInputs());
        self::assertContains('User.create', $ir['processes'][0]->getOutputs());
        self::assertContains('DB.table', $ir['processes'][0]->getOutputs());

        self::assertContains([
            'from' => 'process.LaravelDfd.Tests.Unit.DFDBuilderFixtureController.store',
            'to' => 'store.user',
            'label' => 'Users',
        ], array_map(static fn (DataFlow $flow): array => $flow->toArray(), $ir['flows']));

        self::assertContains([
            'from' => 'process.LaravelDfd.Tests.Unit.DFDBuilderFixtureController.store',
            'to' => 'store.user',
            'label' => 'Users',
        ], array_map(static fn (DataFlow $flow): array => $flow->toArray(), $ir['flows']));
    }

    public function test_it_handles_missing_controllers_gracefully(): void
    {
        Route::get('missing', 'App\\Http\\Controllers\\MissingController@index');

        $ir = (new DFDBuilder())->build();

        self::assertCount(1, $ir['processes']);
        self::assertSame('process.App.Http.Controllers.MissingController.index', $ir['processes'][0]->getId());
        self::assertSame([], $ir['processes'][0]->getOutputs());
        self::assertSame([], $ir['dataStores']);
        self::assertCount(1, $ir['externalEntities']);
        self::assertCount(1, $ir['flows']);
    }
}

final class DFDBuilderFixtureController
{
    public function store(): void
    {
        $user = User::create(['name' => 'A']);
        $user->save();
        DB::table('users')->insert(['name' => 'A']);
    }

    public function update(): void
    {
        User::where('id', 1)->update(['name' => 'B']);
        DB::table('users')->delete();
    }
}
