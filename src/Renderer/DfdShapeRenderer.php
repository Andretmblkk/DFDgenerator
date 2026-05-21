<?php

declare(strict_types=1);

namespace LaravelDfd\Renderer;

use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\HierarchicalProcess;

final class DfdShapeRenderer
{
    /**
     * @param array{x: int, y: int, width: int, height: int} $box
     */
    public function process(HierarchicalProcess $process, array $box): string
    {
        $cx = $box['x'] + (int) ($box['width'] / 2);
        $cy = $box['y'] + (int) ($box['height'] / 2);

        return sprintf(
            '<g class="dfd-node dfd-process" data-id="%s"><ellipse cx="%d" cy="%d" rx="%d" ry="%d"/><text x="%d" y="%d">%s</text></g>',
            $this->escape($process->getId()),
            $cx,
            $cy,
            (int) ($box['width'] / 2),
            (int) ($box['height'] / 2),
            $cx,
            $cy - 8,
            $this->textLines($process->getLabel(), $cx, $cy - 12),
        );
    }

    /**
     * @param array{x: int, y: int, width: int, height: int} $box
     */
    public function externalEntity(ExternalEntityNode $entity, array $box): string
    {
        return sprintf(
            '<g class="dfd-node dfd-entity" data-id="%s"><rect x="%d" y="%d" width="%d" height="%d" rx="4"/><text x="%d" y="%d">%s</text></g>',
            $this->escape($entity->getId()),
            $box['x'],
            $box['y'],
            $box['width'],
            $box['height'],
            $box['x'] + (int) ($box['width'] / 2),
            $box['y'] + (int) ($box['height'] / 2) - 8,
            $this->textLines($entity->getName(), $box['x'] + (int) ($box['width'] / 2), $box['y'] + (int) ($box['height'] / 2) - 12),
        );
    }

    /**
     * @param array{x: int, y: int, width: int, height: int} $box
     */
    public function dataStore(DataStoreNode $store, array $box): string
    {
        $x2 = $box['x'] + $box['width'];
        $y2 = $box['y'] + $box['height'];

        return sprintf(
            '<g class="dfd-node dfd-store" data-id="%s"><path d="M %d %d L %d %d M %d %d L %d %d M %d %d L %d %d"/><text x="%d" y="%d">%s</text></g>',
            $this->escape($store->getId()),
            $box['x'],
            $box['y'],
            $x2,
            $box['y'],
            $box['x'],
            $y2,
            $x2,
            $y2,
            $box['x'] + 14,
            $box['y'],
            $box['x'] + 14,
            $y2,
            $box['x'] + (int) ($box['width'] / 2) + 7,
            $box['y'] + (int) ($box['height'] / 2) - 8,
            $this->textLines($store->getName(), $box['x'] + (int) ($box['width'] / 2) + 7, $box['y'] + (int) ($box['height'] / 2) - 12),
        );
    }

    /**
     * @param array{x: int, y: int} $from
     * @param array{x: int, y: int} $to
     */
    public function flow(array $from, array $to, string $label): string
    {
        if ($from['x'] === $to['x'] || $from['y'] === $to['y']) {
            $path = sprintf('M %d %d L %d %d', $from['x'], $from['y'], $to['x'], $to['y']);
            $labelX = (int) (($from['x'] + $to['x']) / 2);
            $labelY = (int) (($from['y'] + $to['y']) / 2) - 7;
        } else {
            $midY = (int) (($from['y'] + $to['y']) / 2);
            $path = sprintf('M %d %d L %d %d L %d %d L %d %d', $from['x'], $from['y'], $from['x'], $midY, $to['x'], $midY, $to['x'], $to['y']);
            $labelX = (int) (($from['x'] + $to['x']) / 2);
            $labelY = $midY - 7;
        }

        return sprintf(
            '<g class="dfd-flow"><path d="%s" marker-end="url(#arrow)"/><text x="%d" y="%d">%s</text></g>',
            $path,
            $labelX,
            $labelY,
            $this->escape($this->shorten($label)),
        );
    }

    private function textLines(string $text, int $x, int $y): string
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = trim($line . ' ' . $word);

            if (strlen($candidate) > 24 && $line !== '') {
                $lines[] = $line;
                $line = $word;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        $svg = [];
        $offset = 0 - ((count($lines) - 1) * 9);

        foreach ($lines as $lineText) {
            $svg[] = sprintf('<tspan x="%d" dy="%d">%s</tspan>', $x, $offset, $this->escape($lineText));
            $offset = 18;
        }

        return implode('', $svg);
    }

    private function shorten(string $label): string
    {
        $label = trim($label);

        return strlen($label) > 36 ? substr($label, 0, 33) . '...' : $label;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
