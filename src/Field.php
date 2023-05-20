<?php

namespace Danilocgsilva\DatabaseDiscover;

class Field
{
    private string $name;
    private string $type;
    private ?string $null;
    private string $key;
    private ?string $default;
    private string $extra;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function seyNull(string $nullData): self
    {
        $this->null = $nullData;
        return $this;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function setDefault(?string $default): self
    {
        $this->default = $default;
        return $this;
    }

    public function setExtra(string $extra): self
    {
        $this->extra = $extra;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
