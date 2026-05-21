<?php

declare(strict_types=1);

namespace LaravelDfd\Parser;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

final class ASTTraverser extends NodeVisitorAbstract
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $nodes = [];

    /**
     * @param array<int, Node> $ast
     * @return array<int, array<string, mixed>>
     */
    public function traverse(array $ast): array
    {
        $this->nodes = [];

        $traverser = new NodeTraverser();
        $traverser->addVisitor($this);
        $traverser->traverse($ast);

        return $this->nodes;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof StaticCall) {
            $this->nodes[] = [
                'type' => 'StaticCall',
                'name' => $this->name($node->name),
                'target' => $this->name($node->class),
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof MethodCall) {
            $this->nodes[] = [
                'type' => 'MethodCall',
                'name' => $this->name($node->name),
                'target' => $this->exprName($node->var),
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof FuncCall) {
            $this->nodes[] = [
                'type' => 'FunctionCall',
                'name' => $this->name($node->name),
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof Variable) {
            $this->nodes[] = [
                'type' => 'Variable',
                'name' => is_string($node->name) ? $node->name : null,
                'line' => $node->getStartLine(),
            ];
        }

        if ($node instanceof Assign) {
            $this->nodes[] = [
                'type' => 'Assign',
                'name' => $this->exprName($node->var),
                'value' => $this->exprName($node->expr),
                'line' => $node->getStartLine(),
            ];
        }

        return null;
    }

    private function name(Node|Identifier|string $node): ?string
    {
        if ($node instanceof Name) {
            return $node->toString();
        }

        if ($node instanceof Identifier) {
            return $node->toString();
        }

        if ($node instanceof Variable) {
            return is_string($node->name) ? $node->name : null;
        }

        if (is_string($node)) {
            return $node;
        }

        return $node::class;
    }

    private function exprName(Node $node): ?string
    {
        if ($node instanceof Variable) {
            return is_string($node->name) ? $node->name : null;
        }

        if ($node instanceof StaticCall) {
            return $this->name($node->class) . '::' . $this->name($node->name);
        }

        if ($node instanceof MethodCall) {
            return $this->exprName($node->var) . '->' . $this->name($node->name);
        }

        if ($node instanceof FuncCall) {
            return $this->name($node->name);
        }

        if ($node instanceof Name || $node instanceof Identifier) {
            return $this->name($node);
        }

        return $node::class;
    }
}
