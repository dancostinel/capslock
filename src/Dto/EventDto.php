<?php

namespace App\Dto;

readonly class EventDto
{
    public function __construct(
        private int $id,
        private array $data,
        private string $sourceName,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }
}
