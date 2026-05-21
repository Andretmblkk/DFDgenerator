<?php

declare(strict_types=1);

namespace LaravelDfd\IR;

use JsonSerializable;

final class ProcessGroup implements JsonSerializable
{
    /**
     * @param array<int, ProcessNode> $processes
     */
    public function __construct(
        private string $key,
        private string $label,
        private string $number,
        private array $processes = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getProcessId(): string
    {
        return 'process.group.' . $this->key;
    }

    /**
     * @return array<int, ProcessNode>
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }

    public function addProcess(ProcessNode $process): void
    {
        $this->processes[] = $process;
    }

    /**
     * @return array{key: string, label: string, number: string, processId: string, processes: array<int, array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'number' => $this->number,
            'processId' => $this->getProcessId(),
            'processes' => array_map(static fn (ProcessNode $process): array => $process->toArray(), $this->processes),
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
