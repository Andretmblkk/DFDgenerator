<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\HierarchicalProcess;
use LaravelDfd\IR\ProcessGroup;
use LaravelDfd\Support\ProcessNameResolver;

final class Level3Builder
{
    public function __construct(private ProcessNameResolver $nameResolver = new ProcessNameResolver())
    {
    }

    /**
     * @param array<int, ProcessGroup> $groups
     * @param array<int, ExternalEntityNode> $externalEntities
     * @param array<string, array<int, \LaravelDfd\IR\DataStoreNode>> $storesByProcess
     * @return array<int, DFDLevel>
     */
    public function build(array $groups, array $externalEntities, array $storesByProcess): array
    {
        $levels = [];

        foreach ($groups as $group) {
            $level2Index = 1;

            foreach ($group->getProcesses() as $source) {
                if (! $this->nameResolver->shouldDecomposeToLevel3($source)) {
                    $level2Index++;
                    continue;
                }

                $parentId = 'process.level2.' . $group->getKey() . '.' . $level2Index;
                $operations = $this->nameResolver->level3Names($source);
                $processes = [];
                $flows = [];
                $stores = [];
                $previousId = null;

                foreach ($operations as $operationIndex => $operation) {
                    $number = ((int) $group->getNumber()) . '.' . $level2Index . '.' . ($operationIndex + 1);
                    $process = new HierarchicalProcess(
                        'process.level3.' . $group->getKey() . '.' . $level2Index . '.' . ($operationIndex + 1),
                        $number,
                        $operation,
                        3,
                        $parentId,
                        [$source->getId()],
                        $operationIndex === 0 ? $source->getInputs() : [],
                        $operationIndex === count($operations) - 1 ? $source->getOutputs() : [],
                    );
                    $processes[] = $process;

                    if ($previousId === null) {
                        foreach ($externalEntities as $entity) {
                            if ($entity->getName() === 'Payment Gateway') {
                                continue;
                            }

                            $flows[$entity->getId() . '->' . $process->getId()] = new DataFlow(
                                $entity->getId(),
                                $process->getId(),
                                $this->nameResolver->flowLabel($operation),
                            );
                        }
                    } else {
                        $flows[$previousId . '->' . $process->getId()] = new DataFlow(
                            $previousId,
                            $process->getId(),
                            $this->nameResolver->flowLabel($operation),
                        );
                    }

                    $previousId = $process->getId();
                }

                $last = end($processes);

                if ($last instanceof HierarchicalProcess) {
                    foreach ($externalEntities as $entity) {
                        if ($entity->getName() !== 'Payment Gateway') {
                            continue;
                        }

                        $flows[$last->getId() . '->' . $entity->getId()] = new DataFlow($last->getId(), $entity->getId(), 'Request Pembayaran');
                        $flows[$entity->getId() . '->' . $last->getId()] = new DataFlow($entity->getId(), $last->getId(), 'Status Pembayaran');
                    }

                    foreach ($storesByProcess[$source->getId()] ?? [] as $store) {
                        $stores[$store->getId()] = $store;
                        $flows[$last->getId() . '->' . $store->getId()] = new DataFlow(
                            $last->getId(),
                            $store->getId(),
                            $this->nameResolver->flowLabel($store->getName()),
                        );
                    }
                }

                $levels[] = new DFDLevel(
                    'level-3-' . $group->getKey() . '.' . $level2Index,
                    3,
                    'Level 3 ' . $this->nameResolver->level2Name($source),
                    $processes,
                    array_values($stores),
                    $externalEntities,
                    array_values($flows),
                    $parentId,
                );

                $level2Index++;
            }
        }

        return $levels;
    }
}
