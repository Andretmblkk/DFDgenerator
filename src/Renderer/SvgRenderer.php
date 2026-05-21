<?php

declare(strict_types=1);

namespace LaravelDfd\Renderer;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DFDLevel;

final class SvgRenderer
{
    public function __construct(private DfdShapeRenderer $shapeRenderer = new DfdShapeRenderer())
    {
    }

    public function render(DFDLevel $level): string
    {
        return $this->renderDocument([$level], $level->getTitle());
    }

    /**
     * @param array<int, DFDLevel> $levels
     */
    public function renderDocument(array $levels, string $title): string
    {
        if ($levels === []) {
            return $this->emptyDocument($title);
        }

        $width = 1180;
        $offset = 0;
        $sections = [];

        foreach ($levels as $level) {
            $layout = $this->layout($level);
            $height = max(560, $layout['height']);
            $sections[] = sprintf(
                '<g transform="translate(0 %d)">%s</g>',
                $offset,
                $this->levelContent($level, $layout['boxes']),
            );
            $offset += $height + 36;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-label="%s"><defs><marker id="arrow" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto"><path d="M2,2 L10,6 L2,10 z"/></marker></defs><style>%s</style>%s</svg>',
            $width,
            max(560, $offset),
            $this->escape($title),
            $this->style(),
            implode('', $sections),
        );
    }

    /**
     * @param array<string, array{x: int, y: int, width: int, height: int}> $boxes
     */
    private function levelContent(DFDLevel $level, array $boxes): string
    {
        $nodes = [];

        foreach ($level->getExternalEntities() as $entity) {
            $nodes[] = $this->shapeRenderer->externalEntity($entity, $boxes[$entity->getId()]);
        }

        foreach ($level->getProcesses() as $process) {
            $nodes[] = $this->shapeRenderer->process($process, $boxes[$process->getId()]);
        }

        foreach ($level->getDataStores() as $store) {
            $nodes[] = $this->shapeRenderer->dataStore($store, $boxes[$store->getId()]);
        }

        $flows = array_map(fn (DataFlow $flow): string => $this->flow($flow, $boxes), $level->getFlows());

        return sprintf(
            '<text class="dfd-title" x="590" y="38">%s</text>%s%s',
            $this->escape($level->getTitle()),
            implode('', $flows),
            implode('', $nodes),
        );
    }

    /**
     * @param array<string, array{x: int, y: int, width: int, height: int}> $boxes
     */
    private function flow(DataFlow $flow, array $boxes): string
    {
        if (! isset($boxes[$flow->getFrom()], $boxes[$flow->getTo()])) {
            return '';
        }

        $from = $boxes[$flow->getFrom()];
        $to = $boxes[$flow->getTo()];
        $fromCenter = ['x' => $from['x'] + (int) ($from['width'] / 2), 'y' => $from['y'] + (int) ($from['height'] / 2)];
        $toCenter = ['x' => $to['x'] + (int) ($to['width'] / 2), 'y' => $to['y'] + (int) ($to['height'] / 2)];

        if (abs($fromCenter['x'] - $toCenter['x']) < 80) {
            $fromPoint = ['x' => $fromCenter['x'], 'y' => $from['y'] + $from['height']];
            $toPoint = ['x' => $toCenter['x'], 'y' => $to['y']];
        } else {
            $fromPoint = $fromCenter['x'] <= $toCenter['x']
                ? ['x' => $from['x'] + $from['width'], 'y' => $fromCenter['y']]
                : ['x' => $from['x'], 'y' => $fromCenter['y']];
            $toPoint = $fromCenter['x'] <= $toCenter['x']
                ? ['x' => $to['x'], 'y' => $toCenter['y']]
                : ['x' => $to['x'] + $to['width'], 'y' => $toCenter['y']];
        }

        return $this->shapeRenderer->flow(
            $fromPoint,
            $toPoint,
            $flow->getLabel(),
        );
    }

    /**
     * @return array{height: int, boxes: array<string, array{x: int, y: int, width: int, height: int}>}
     */
    private function layout(DFDLevel $level): array
    {
        $boxes = [];
        $rows = max(count($level->getProcesses()), count($level->getDataStores()), 1);
        $height = 130 + ($rows * 98) + 80;

        foreach ($level->getExternalEntities() as $index => $entity) {
            $boxes[$entity->getId()] = ['x' => 58, 'y' => $this->balancedY($index, count($level->getExternalEntities()), $height, 70), 'width' => 175, 'height' => 70];
        }

        foreach ($level->getProcesses() as $index => $process) {
            $boxes[$process->getId()] = ['x' => 415, 'y' => 92 + ($index * 98), 'width' => 320, 'height' => 70];
        }

        foreach ($level->getDataStores() as $index => $store) {
            $boxes[$store->getId()] = ['x' => 890, 'y' => $this->balancedY($index, count($level->getDataStores()), $height, 58), 'width' => 230, 'height' => 58];
        }

        return [
            'height' => $height,
            'boxes' => $boxes,
        ];
    }

    private function balancedY(int $index, int $count, int $height, int $boxHeight): int
    {
        if ($count <= 0) {
            return 120;
        }

        $available = max(120, $height - 170);
        $gap = $count === 1 ? 0 : (int) (($available - $boxHeight) / max(1, $count - 1));

        return 100 + ($index * max($boxHeight + 18, $gap));
    }

    private function emptyDocument(string $title): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1180 560" role="img" aria-label="%s"><style>%s</style><text class="dfd-title" x="590" y="280">%s</text></svg>',
            $this->escape($title),
            $this->style(),
            $this->escape($title),
        );
    }

    private function style(): string
    {
        return '.dfd-title{font:700 22px Arial,sans-serif;text-anchor:middle;fill:#172033}.dfd-node rect,.dfd-node ellipse,.dfd-store path{fill:#fff;stroke:#172033;stroke-width:2}.dfd-process ellipse{fill:#f9fcff}.dfd-entity rect{fill:#fff}.dfd-store path{fill:none}.dfd-node text{font:600 13px Arial,sans-serif;text-anchor:middle;dominant-baseline:middle;fill:#172033}.dfd-store text{font-weight:600}.dfd-flow path{fill:none;stroke:#2f3b4f;stroke-width:1.6;shape-rendering:geometricPrecision}.dfd-flow text{font:12px Arial,sans-serif;text-anchor:middle;fill:#2f3b4f;paint-order:stroke;stroke:#fff;stroke-width:5px;stroke-linejoin:round}marker path{fill:#2f3b4f}@media(prefers-color-scheme:dark){.dfd-title,.dfd-node text{fill:#eef3ff}.dfd-node rect,.dfd-node ellipse{fill:#152033;stroke:#d8e1f3}.dfd-process ellipse{fill:#172a42}.dfd-store path{stroke:#d8e1f3}.dfd-flow path{stroke:#c7d2e6}.dfd-flow text{fill:#eef3ff;stroke:#111827}marker path{fill:#c7d2e6}}';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
