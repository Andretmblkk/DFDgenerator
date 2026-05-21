<?php

declare(strict_types=1);

namespace LaravelDfd\IR;

use JsonSerializable;

final class HierarchicalProcess implements JsonSerializable
{
    /**
     * @param array<int, string> $sourceProcessIds
     * @param array<int, string> $inputs
     * @param array<int, string> $outputs
     */
    public function __construct(
        private string $id,
        private string $number,
        private string $name,
        private int $level,
        private ?string $parentId = null,
        private array $sourceProcessIds = [],
        private array $inputs = [],
        private array $outputs = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return trim($this->number . ' ' . $this->name);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    /**
     * @return array<int, string>
     */
    public function getSourceProcessIds(): array
    {
        return $this->sourceProcessIds;
    }

    /**
     * @return array<int, string>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @return array<int, string>
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @return array{
     *     id: string,
     *     number: string,
     *     name: string,
     *     label: string,
     *     level: int,
     *     parentId: string|null,
     *     sourceProcessIds: array<int, string>,
     *     inputs: array<int, string>,
     *     outputs: array<int, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'name' => $this->name,
            'label' => $this->getLabel(),
            'level' => $this->level,
            'parentId' => $this->parentId,
            'sourceProcessIds' => $this->sourceProcessIds,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
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
