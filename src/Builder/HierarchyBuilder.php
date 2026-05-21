<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessGroup;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Support\SemanticClassifier;

final class HierarchyBuilder
{
    public function __construct(
        private DFDBuilder $dfdBuilder = new DFDBuilder(),
        private SemanticClassifier $classifier = new SemanticClassifier(),
        private Level0Builder $level0Builder = new Level0Builder(),
        private Level1Builder $level1Builder = new Level1Builder(),
        private Level2Builder $level2Builder = new Level2Builder(),
        private Level3Builder $level3Builder = new Level3Builder(),
    ) {
    }

    /**
     * @return array{
     *     system: string,
     *     selectedLevel: int,
     *     meta: array<string, mixed>,
     *     groups: array<int, ProcessGroup>,
     *     levels: array<int, DFDLevel>
     * }
     */
    public function build(int $maxLevel = 3): array
    {
        $maxLevel = max(0, min(3, $maxLevel));
        $flat = $this->dfdBuilder->build();
        $groups = $this->groups($flat['processes']);
        $storesByProcess = $this->storesByProcess($flat['flows'], $flat['dataStores']);
        $systemName = $this->systemName();

        $levels = [
            $this->level0Builder->build($systemName, $flat['externalEntities'], $flat['dataStores']),
        ];

        if ($maxLevel >= 1) {
            $levels[] = $this->level1Builder->build($groups, $flat['externalEntities'], $flat['dataStores'], $storesByProcess);
        }

        if ($maxLevel >= 2) {
            array_push($levels, ...$this->level2Builder->build($groups, $flat['externalEntities'], $storesByProcess));
        }

        if ($maxLevel >= 3) {
            array_push($levels, ...$this->level3Builder->build($groups, $flat['externalEntities'], $storesByProcess));
        }

        $warnings = $flat['processes'] === [] ? ['Tidak ada business route yang terdeteksi.'] : [];

        if ($maxLevel >= 3 && ! array_filter($levels, static fn (DFDLevel $level): bool => $level->getLevel() === 3)) {
            $warnings[] = 'Tidak ada proses kompleks untuk Level 3. Diagram Level 3 dibuat sebagai empty state.';
        }

        return [
            'system' => $systemName,
            'selectedLevel' => $maxLevel,
            'meta' => [
                'generatedAt' => date('c'),
                'routes' => count($flat['processes']),
                'processes' => array_sum(array_map(static fn (DFDLevel $level): int => count($level->getProcesses()), $levels)),
                'dataStores' => count($flat['dataStores']),
                'externalEntities' => count($flat['externalEntities']),
                'warnings' => $warnings,
                'creator' => 'Andre Tumbelaka',
            ],
            'groups' => $groups,
            'levels' => $levels,
        ];
    }

    /**
     * @param array<int, ProcessNode> $processes
     * @return array<int, ProcessGroup>
     */
    private function groups(array $processes): array
    {
        $groups = [];
        $numbers = [];

        foreach ($processes as $process) {
            $classification = $this->classifier->classify($process);
            $key = $classification['key'];

            if (! isset($groups[$key])) {
                $numbers[$key] = count($groups) + 1;
                $groups[$key] = new ProcessGroup($key, $classification['label'], $numbers[$key] . '.0');
            }

            $groups[$key]->addProcess($process);
        }

        uasort($groups, function (ProcessGroup $left, ProcessGroup $right): int {
            return $this->groupPriority($left->getKey()) <=> $this->groupPriority($right->getKey());
        });

        $renumbered = [];

        foreach (array_values($groups) as $index => $group) {
            $renumbered[] = new ProcessGroup($group->getKey(), $group->getLabel(), ($index + 1) . '.0', $group->getProcesses());
        }

        return $renumbered;
    }

    private function groupPriority(string $key): int
    {
        return [
            'authentication' => 10,
            'product' => 20,
            'checkout' => 30,
            'payment' => 40,
            'transaction' => 50,
        ][$key] ?? 100;
    }

    /**
     * @param array<int, DataFlow> $flows
     * @param array<int, DataStoreNode> $dataStores
     * @return array<string, array<int, DataStoreNode>>
     */
    private function storesByProcess(array $flows, array $dataStores): array
    {
        $storesById = [];

        foreach ($dataStores as $store) {
            $storesById[$store->getId()] = $store;
        }

        $storesByProcess = [];

        foreach ($flows as $flow) {
            $store = $storesById[$flow->getTo()] ?? null;

            if ($store === null) {
                continue;
            }

            $storesByProcess[$flow->getFrom()][$store->getId()] = $store;
        }

        return array_map(static fn (array $stores): array => array_values($stores), $storesByProcess);
    }

    private function systemName(): string
    {
        if (function_exists('config')) {
            $configured = config('laravel-dfd.system_name', config('dfd.system_name', null));

            if (is_string($configured) && $configured !== '') {
                return $configured;
            }

            $appName = config('app.name');

            if (is_string($appName) && $appName !== '') {
                return $appName . ' System';
            }
        }

        return 'Laravel Application System';
    }
}
