<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use LaravelDfd\Generator\MermaidGenerator;
use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Tests\TestCase;

final class MermaidGeneratorTest extends TestCase
{
    public function test_it_generates_mermaid_flowchart_from_ir_nodes_and_flows(): void
    {
        $output = (new MermaidGenerator())->generate([
            new ExternalEntityNode('User', 'User'),
            new ProcessNode('Login', 'Login'),
            new DataStoreNode('UsersDB', 'UsersDB', 'database'),
        ], [
            new DataFlow('User', 'Login', ''),
            new DataFlow('Login', 'UsersDB', ''),
        ]);

        self::assertSame(implode(PHP_EOL, [
            'flowchart TD',
            '',
            'User[User]',
            'Login[Login]',
            'UsersDB[(UsersDB)]',
            '',
            'User --> Login',
            'Login --> UsersDB',
            '',
        ]), $output);
    }

    public function test_it_sanitizes_ids_and_escapes_labels_deterministically(): void
    {
        $output = (new MermaidGenerator())->generate([
            new ExternalEntityNode('entity.user', 'User "Admin"'),
            new ProcessNode('process.login', 'Login [OAuth]'),
            new DataStoreNode('store.users', 'Users (DB)', 'database'),
        ], [
            new DataFlow('entity.user', 'process.login', 'request|credentials'),
            new DataFlow('process.login', 'store.users', ''),
        ]);

        self::assertSame(implode(PHP_EOL, [
            'flowchart TD',
            '',
            'entity_user["User \"Admin\""]',
            'process_login["Login [OAuth]"]',
            'store_users[("Users (DB)")]',
            '',
            'entity_user -->|request\|credentials| process_login',
            'process_login --> store_users',
            '',
        ]), $output);
    }
}
