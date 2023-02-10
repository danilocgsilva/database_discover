<?php

namespace Danilocgsilva\DatabaseDiscover;

class Table
{
    private string $name;
    private ?int $size = NULL;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getSize(): int|null
    {
        return $this->size;
    }
}
