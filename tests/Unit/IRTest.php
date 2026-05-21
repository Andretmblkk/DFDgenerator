<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Tests\TestCase;

final class IRTest extends TestCase
{
    public function test_process_node_exposes_values_as_array_and_json(): void
    {
        $node = new ProcessNode('process.users.store', 'Store User', ['request'], ['user']);

        self::assertSame('process.users.store', $node->getId());
        self::assertSame('Store User', $node->getName());
        self::assertSame(['request'], $node->getInputs());
        self::assertSame(['user'], $node->getOutputs());
        self::assertSame([
            'id' => 'process.users.store',
            'name' => 'Store User',
            'inputs' => ['request'],
            'outputs' => ['user'],
        ], $node->toArray());
        self::assertSame($node->toArray(), json_decode((string) json_encode($node), true));
    }

    public function test_data_store_node_exposes_values_as_array_and_json(): void
    {
        $node = new DataStoreNode('store.users', 'users', 'database_table');

        self::assertSame('store.users', $node->getId());
        self::assertSame('users', $node->getName());
        self::assertSame('database_table', $node->getType());
        self::assertSame([
            'id' => 'store.users',
            'name' => 'users',
            'type' => 'database_table',
        ], $node->toArray());
        self::assertSame($node->toArray(), json_decode((string) json_encode($node), true));
    }

    public function test_external_entity_node_exposes_values_as_array_and_json(): void
    {
        $node = new ExternalEntityNode('entity.user', 'User');

        self::assertSame('entity.user', $node->getId());
        self::assertSame('User', $node->getName());
        self::assertSame([
            'id' => 'entity.user',
            'name' => 'User',
        ], $node->toArray());
        self::assertSame($node->toArray(), json_decode((string) json_encode($node), true));
    }

    public function test_data_flow_exposes_values_as_array_and_json(): void
    {
        $flow = new DataFlow('entity.user', 'process.users.store', 'Submit user data');

        self::assertSame('entity.user', $flow->getFrom());
        self::assertSame('process.users.store', $flow->getTo());
        self::assertSame('Submit user data', $flow->getLabel());
        self::assertSame([
            'from' => 'entity.user',
            'to' => 'process.users.store',
            'label' => 'Submit user data',
        ], $flow->toArray());
        self::assertSame($flow->toArray(), json_decode((string) json_encode($flow), true));
    }
}
