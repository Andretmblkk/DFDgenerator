<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\HierarchicalProcess;

final class Level0Builder
{
    /**
     * @param array<int, DataStoreNode> $dataStores
     * @param array<int, ExternalEntityNode> $externalEntities
     */
    public function build(string $systemName, array $externalEntities, array $dataStores): DFDLevel
    {
        $system = new HierarchicalProcess('process.system', '0', $systemName, 0);
        $flows = [];

        foreach ($externalEntities as $entity) {
            $label = $entity->getName() === 'Payment Gateway' ? 'Status Pembayaran' : 'Permintaan Layanan';
            $flows[] = new DataFlow($entity->getId(), $system->getId(), $label);
        }

        foreach ($dataStores as $store) {
            $flows[] = new DataFlow($system->getId(), $store->getId(), $store->getName());
        }

        return new DFDLevel(
            'level-0',
            0,
            'Level 0 Context Diagram',
            [$system],
            $dataStores,
            $externalEntities,
            $flows,
            null,
            ['level-1'],
        );
    }
}
