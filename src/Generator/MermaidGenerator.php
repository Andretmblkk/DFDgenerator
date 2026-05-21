<?php

declare(strict_types=1);

namespace LaravelDfd\Generator;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessNode;

final class MermaidGenerator
{
    /**
     * @param array<int, ProcessNode|DataStoreNode|ExternalEntityNode> $nodes
     * @param array<int, DataFlow> $flows
     */
    public function generate(array $nodes, array $flows): string
    {
        $lines = ['flowchart TD', ''];

        foreach ($nodes as $node) {
            $lines[] = $this->renderNode($node);
        }

        if ($nodes !== [] && $flows !== []) {
            $lines[] = '';
        }

        foreach ($flows as $flow) {
            $lines[] = $this->renderFlow($flow);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function renderNode(ProcessNode|DataStoreNode|ExternalEntityNode $node): string
    {
        $id = $this->nodeId($node->getId());
        $label = $this->label($node->getName());

        if ($node instanceof DataStoreNode) {
            return $id . '[(' . $label . ')]';
        }

        return $id . '[' . $label . ']';
    }

    private function renderFlow(DataFlow $flow): string
    {
        $from = $this->nodeId($flow->getFrom());
        $to = $this->nodeId($flow->getTo());
        $label = $flow->getLabel();

        if ($label === '') {
            return $from . ' --> ' . $to;
        }

        return $from . ' -->|' . $this->edgeLabel($label) . '| ' . $to;
    }

    private function nodeId(string $id): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_]/', '_', $id);

        return $normalized === null || $normalized === '' ? 'node' : $normalized;
    }

    private function label(string $label): string
    {
        if (preg_match('/^[A-Za-z0-9_ ]+$/', $label) === 1) {
            return $label;
        }

        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $label) . '"';
    }

    private function edgeLabel(string $label): string
    {
        return str_replace(['\\', '|'], ['\\\\', '\|'], $label);
    }
}
