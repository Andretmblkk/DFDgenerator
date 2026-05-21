<?php

declare(strict_types=1);

namespace LaravelDfd\Scanner;

use LaravelDfd\Parser\ASTParser;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class ModelScanner extends NodeVisitorAbstract
{
    private const MODEL_METHODS = [
        'create',
        'find',
        'where',
        'update',
        'delete',
        'save',
    ];

    private const DB_METHODS = [
        'table',
        'select',
        'insert',
        'update',
        'delete',
    ];

    /**
     * @var array<string, true>
     */
    private array $models = [];

    /**
     * @var array<string, true>
     */
    private array $tables = [];

    /**
     * @var array<int, array{type: string, target: string|null}>
     */
    private array $operations = [];

    /**
     * @var array<string, string>
     */
    private array $variableModels = [];

    /**
     * @var array<string, string>
     */
    private array $variableTables = [];

    /**
     * @return array{models: array<int, string>, tables: array<int, string>, operations: array<int, array{type: string, target: string|null}>}
     */
    public function scanSource(string $source): array
    {
        return $this->scanAst((new ASTParser())->parse($source));
    }

    /**
     * @return array{models: array<int, string>, tables: array<int, string>, operations: array<int, array{type: string, target: string|null}>}
     */
    public function scanFile(string $path): array
    {
        return $this->scanAst((new ASTParser())->parseFile($path));
    }

    /**
     * @param array<int, Node> $ast
     * @return array{models: array<int, string>, tables: array<int, string>, operations: array<int, array{type: string, target: string|null}>}
     */
    public function scanAst(array $ast): array
    {
        $this->models = [];
        $this->tables = [];
        $this->operations = [];
        $this->variableModels = [];
        $this->variableTables = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($ast);

        return [
            'models' => array_values(array_keys($this->models)),
            'tables' => array_values(array_keys($this->tables)),
            'operations' => $this->operations,
        ];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Assign) {
            $this->recordAssignment($node);
        }

        if ($node instanceof StaticCall) {
            $this->recordStaticCall($node);
        }

        if ($node instanceof MethodCall) {
            $this->recordMethodCall($node);
        }

        return null;
    }

    private function recordAssignment(Assign $node): void
    {
        if (! $node->var instanceof Variable || ! is_string($node->var->name)) {
            return;
        }

        $model = $this->modelTarget($node->expr);

        if ($model !== null) {
            $this->variableModels[$node->var->name] = $model;
        }

        $table = $this->tableTarget($node->expr);

        if ($table !== null) {
            $this->variableTables[$node->var->name] = $table;
        }
    }

    private function recordStaticCall(StaticCall $node): void
    {
        $class = $this->name($node->class);
        $method = $this->name($node->name);

        if ($class === null || $method === null) {
            return;
        }

        if ($this->isDbFacade($class) && in_array($method, self::DB_METHODS, true)) {
            $target = $method === 'table' ? $this->firstStringArgument($node) : null;

            if ($target !== null) {
                $this->tables[$target] = true;
            }

            $this->operations[] = [
                'type' => 'db_' . $method,
                'target' => $target,
            ];

            return;
        }

        if (in_array($method, self::MODEL_METHODS, true)) {
            $model = $this->shortClassName($class);
            $this->models[$model] = true;
            $this->operations[] = [
                'type' => 'model_' . $method,
                'target' => $model,
            ];
        }
    }

    private function recordMethodCall(MethodCall $node): void
    {
        $method = $this->name($node->name);

        if ($method === null) {
            return;
        }

        if (in_array($method, self::MODEL_METHODS, true)) {
            $model = $this->modelTarget($node->var);

            if ($model !== null) {
                $this->models[$model] = true;
                $this->operations[] = [
                    'type' => 'model_' . $method,
                    'target' => $model,
                ];
            }
        }

        if (in_array($method, self::DB_METHODS, true)) {
            $table = $this->tableTarget($node->var);

            if ($table !== null) {
                $this->tables[$table] = true;
                $this->operations[] = [
                    'type' => 'db_' . $method,
                    'target' => $table,
                ];
            }
        }
    }

    private function modelTarget(Node $node): ?string
    {
        if ($node instanceof Variable && is_string($node->name)) {
            return $this->variableModels[$node->name] ?? null;
        }

        if ($node instanceof StaticCall) {
            $class = $this->name($node->class);
            $method = $this->name($node->name);

            if ($class !== null && ! $this->isDbFacade($class) && in_array((string) $method, self::MODEL_METHODS, true)) {
                return $this->shortClassName($class);
            }
        }

        if ($node instanceof MethodCall) {
            return $this->modelTarget($node->var);
        }

        if ($node instanceof New_) {
            $class = $this->name($node->class);

            return $class !== null ? $this->shortClassName($class) : null;
        }

        return null;
    }

    private function tableTarget(Node $node): ?string
    {
        if ($node instanceof Variable && is_string($node->name)) {
            return $this->variableTables[$node->name] ?? null;
        }

        if ($node instanceof StaticCall && $this->isDbFacade((string) $this->name($node->class)) && $this->name($node->name) === 'table') {
            return $this->firstStringArgument($node);
        }

        if ($node instanceof MethodCall) {
            return $this->tableTarget($node->var);
        }

        return null;
    }

    private function firstStringArgument(StaticCall|MethodCall $node): ?string
    {
        $argument = $node->args[0] ?? null;

        if (! $argument instanceof Arg || ! $argument->value instanceof String_) {
            return null;
        }

        return $argument->value->value;
    }

    private function name(Node|Identifier|string $node): ?string
    {
        if ($node instanceof Name) {
            return $node->toString();
        }

        if ($node instanceof Identifier) {
            return $node->toString();
        }

        if (is_string($node)) {
            return $node;
        }

        return null;
    }

    private function isDbFacade(string $class): bool
    {
        return $this->shortClassName($class) === 'DB';
    }

    private function shortClassName(string $class): string
    {
        $parts = explode('\\', $class);

        return (string) end($parts);
    }
}
