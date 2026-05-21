<?php

declare(strict_types=1);

namespace LaravelDfd\IR;

use JsonSerializable;

final class DataFlow implements JsonSerializable
{
    public function __construct(
        private string $from,
        private string $to,
        private string $label,
    ) {
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array{from: string, to: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'label' => $this->label,
        ];
    }

    /**
     * @return array{from: string, to: string, label: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
