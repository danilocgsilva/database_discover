<?php

namespace Danilocgsilva\DatabaseDiscover;

class Field
{
    private string $field;
    private string $type;
    private ?string $null;
    private string $key;
    private ?string $default;
    private string $extra;

    public function setField(string $field): self
    {
        $this->field = $field;
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
        return $this->field;
    }
}
