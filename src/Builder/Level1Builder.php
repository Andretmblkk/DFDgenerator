<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\HierarchicalProcess;
use LaravelDfd\IR\ProcessGroup;

final class Level1Builder
{
    /**
     * @param array<int, ProcessGroup> $groups
     * @param array<int, DataStoreNode> $dataStores
     * @param array<int, ExternalEntityNode> $externalEntities
     * @param array<string, array<int, DataStoreNode>> $storesByProcess
     */
    public function build(array $groups, array $externalEntities, array $dataStores, array $storesByProcess): DFDLevel
    {
        $processes = [];
        $flows = [];

        foreach ($groups as $group) {
            $sourceIds = array_map(static fn ($process): string => $process->getId(), $group->getProcesses());
            $process = new HierarchicalProcess(
                $group->getProcessId(),
                $group->getNumber(),
                $group->getLabel(),
                1,
                'process.system',
                $sourceIds,
            );
            $processes[] = $process;

            foreach ($externalEntities as $entity) {
                if ($entity->getName() === 'Payment Gateway' && ! str_contains(strtolower($group->getLabel()), 'pembayaran')) {
                    continue;
                }

                $label = $entity->getName() === 'Payment Gateway' ? 'Konfirmasi Pembayaran' : 'Data Pengguna';
                $flows[$entity->getId() . '->' . $process->getId()] = new DataFlow($entity->getId(), $process->getId(), $label);
            }

            foreach ($group->getProcesses() as $source) {
                foreach ($storesByProcess[$source->getId()] ?? [] as $store) {
                    $flows[$process->getId() . '->' . $store->getId()] = new DataFlow($process->getId(), $store->getId(), $store->getName());
                }
            }
        }

        return new DFDLevel(
            'level-1',
            1,
            'Level 1 Process Decomposition',
            $processes,
            $dataStores,
            $externalEntities,
            array_values($flows),
            'process.system',
            array_map(static fn (ProcessGroup $group): string => 'level-2-' . $group->getKey(), $groups),
        );
    }
}
