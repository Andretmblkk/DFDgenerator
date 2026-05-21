<?php

declare(strict_types=1);

namespace LaravelDfd\IR;

use JsonSerializable;

final class DFDLevel implements JsonSerializable
{
    /**
     * @param array<int, HierarchicalProcess> $processes
     * @param array<int, DataStoreNode> $dataStores
     * @param array<int, ExternalEntityNode> $externalEntities
     * @param array<int, DataFlow> $flows
     * @param array<int, string> $childLevelIds
     */
    public function __construct(
        private string $id,
        private int $level,
        private string $title,
        private array $processes = [],
        private array $dataStores = [],
        private array $externalEntities = [],
        private array $flows = [],
        private ?string $parentProcessId = null,
        private array $childLevelIds = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array<int, HierarchicalProcess>
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }

    /**
     * @return array<int, DataStoreNode>
     */
    public function getDataStores(): array
    {
        return $this->dataStores;
    }

    /**
     * @return array<int, ExternalEntityNode>
     */
    public function getExternalEntities(): array
    {
        return $this->externalEntities;
    }

    /**
     * @return array<int, DataFlow>
     */
    public function getFlows(): array
    {
        return $this->flows;
    }

    public function getParentProcessId(): ?string
    {
        return $this->parentProcessId;
    }

    /**
     * @return array<int, string>
     */
    public function getChildLevelIds(): array
    {
        return $this->childLevelIds;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'title' => $this->title,
            'parentProcessId' => $this->parentProcessId,
            'childLevelIds' => $this->childLevelIds,
            'processes' => array_map(static fn (HierarchicalProcess $process): array => $process->toArray(), $this->processes),
            'dataStores' => array_map(static fn (DataStoreNode $store): array => $store->toArray(), $this->dataStores),
            'externalEntities' => array_map(static fn (ExternalEntityNode $entity): array => $entity->toArray(), $this->externalEntities),
            'flows' => array_map(static fn (DataFlow $flow): array => $flow->toArray(), $this->flows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
