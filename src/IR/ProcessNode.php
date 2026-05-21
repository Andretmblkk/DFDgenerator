<?php

declare(strict_types=1);

namespace LaravelDfd\IR;

use JsonSerializable;

final class ProcessNode implements JsonSerializable
{
    /**
     * @param array<int, string> $inputs
     * @param array<int, string> $outputs
     */
    public function __construct(
        private string $id,
        private string $name,
        private array $inputs = [],
        private array $outputs = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
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
     * @return array{id: string, name: string, inputs: array<int, string>, outputs: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
        ];
    }

    /**
     * @return array{id: string, name: string, inputs: array<int, string>, outputs: array<int, string>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
