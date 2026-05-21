<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\HierarchicalProcess;
use LaravelDfd\IR\ProcessGroup;
use LaravelDfd\Support\ProcessNameResolver;

final class Level2Builder
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
            $processes = [];
            $stores = [];
            $flows = [];
            $childLevelIds = [];
            $sequence = 1;
            $previousId = null;

            foreach ($group->getProcesses() as $sourceIndex => $source) {
                $steps = $this->nameResolver->level2Steps($source, $group->getLabel());
                $firstStepId = null;

                foreach ($steps as $stepIndex => $step) {
                    $process = new HierarchicalProcess(
                        'process.level2.' . $group->getKey() . '.' . $sequence,
                        ((int) $group->getNumber()) . '.' . $sequence,
                        $step,
                        2,
                        $group->getProcessId(),
                        [$source->getId()],
                        $stepIndex === 0 ? $source->getInputs() : [],
                        $stepIndex === count($steps) - 1 ? $source->getOutputs() : [],
                    );
                    $processes[] = $process;
                    $firstStepId ??= $process->getId();

                    if ($previousId !== null) {
                        $flows[$previousId . '->' . $process->getId()] = new DataFlow(
                            $previousId,
                            $process->getId(),
                            $this->nameResolver->flowLabel($step),
                        );
                    }

                    if ($this->isStoreStep($step)) {
                        foreach ($storesByProcess[$source->getId()] ?? [] as $store) {
                            $stores[$store->getId()] = $store;
                            $flows[$process->getId() . '->' . $store->getId()] = new DataFlow(
                                $process->getId(),
                                $store->getId(),
                                $this->nameResolver->flowLabel($store->getName()),
                            );
                        }
                    }

                    if ($this->isPaymentStep($step)) {
                        foreach ($externalEntities as $entity) {
                            if ($entity->getName() !== 'Payment Gateway') {
                                continue;
                            }

                            $flows[$process->getId() . '->' . $entity->getId()] = new DataFlow($process->getId(), $entity->getId(), 'Request Pembayaran');
                            $flows[$entity->getId() . '->' . $process->getId()] = new DataFlow($entity->getId(), $process->getId(), 'Status Pembayaran');
                        }
                    }

                    $previousId = $process->getId();
                    $sequence++;
                }

                if ($firstStepId !== null && $sourceIndex === 0) {
                    foreach ($externalEntities as $entity) {
                        if ($entity->getName() === 'Payment Gateway') {
                            continue;
                        }

                        $flows[$entity->getId() . '->' . $firstStepId] = new DataFlow(
                            $entity->getId(),
                            $firstStepId,
                            $this->nameResolver->flowLabel($group->getLabel()),
                        );
                    }
                }

                if ($this->nameResolver->shouldDecomposeToLevel3($source)) {
                    $childLevelIds[] = 'level-3-' . $group->getKey() . '.' . ($sourceIndex + 1);
                }
            }

            $levels[] = new DFDLevel(
                'level-2-' . $group->getKey(),
                2,
                'Level 2 ' . $group->getLabel(),
                $processes,
                array_values($stores),
                $externalEntities,
                array_values($flows),
                $group->getProcessId(),
                $childLevelIds,
            );
        }

        return $levels;
    }

    private function isStoreStep(string $step): bool
    {
        $lower = strtolower($step);

        return str_contains($lower, 'ambil')
            || str_contains($lower, 'simpan')
            || str_contains($lower, 'update')
            || str_contains($lower, 'perbarui')
            || str_contains($lower, 'kurangi')
            || str_contains($lower, 'buat transaksi');
    }

    private function isPaymentStep(string $step): bool
    {
        $lower = strtolower($step);

        return str_contains($lower, 'payment gateway')
            || str_contains($lower, 'proses pembayaran')
            || str_contains($lower, 'request pembayaran');
    }
}
