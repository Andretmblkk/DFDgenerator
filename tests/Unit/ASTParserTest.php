<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use LaravelDfd\Parser\ASTParser;
use LaravelDfd\Parser\ASTTraverser;
use LaravelDfd\Tests\TestCase;
use PhpParser\Node;

final class ASTParserTest extends TestCase
{
    public function test_it_parses_php_source_into_ast(): void
    {
        $ast = (new ASTParser())->parse('<?php $name = trim($input);');

        self::assertNotEmpty($ast);
        self::assertContainsOnlyInstancesOf(Node::class, $ast);
    }

    public function test_it_traverses_ast_and_returns_normalized_nodes(): void
    {
        $source = <<<'PHP'
        <?php

        $user = UserFactory::make();
        $user->save();
        logger($user);
        PHP;

        $nodes = (new ASTTraverser())->traverse((new ASTParser())->parse($source));

        self::assertContains([
            'type' => 'Assign',
            'name' => 'user',
            'value' => 'UserFactory::make',
            'line' => 3,
        ], $nodes);

        self::assertContains([
            'type' => 'StaticCall',
            'name' => 'make',
            'target' => 'UserFactory',
            'line' => 3,
        ], $nodes);

        self::assertContains([
            'type' => 'MethodCall',
            'name' => 'save',
            'target' => 'user',
            'line' => 4,
        ], $nodes);

        self::assertContains([
            'type' => 'FunctionCall',
            'name' => 'logger',
            'line' => 5,
        ], $nodes);

        self::assertContains([
            'type' => 'Variable',
            'name' => 'user',
            'line' => 3,
        ], $nodes);
    }

    public function test_it_parses_php_source_files(): void
    {
        $ast = (new ASTParser())->parseFile(__DIR__ . '/../Fixtures/AstExample.php');
        $nodes = (new ASTTraverser())->traverse($ast);

        self::assertContains([
            'type' => 'FunctionCall',
            'name' => 'logger',
            'line' => 5,
        ], $nodes);
    }
}
