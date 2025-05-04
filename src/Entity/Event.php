<?php

namespace App\Entity;

class Event
{
    private int $id;
    private string $name;
    private string $data;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
