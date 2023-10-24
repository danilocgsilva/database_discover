<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;

class Table
{
    private string $name;
    private ?int $size = NULL;
    private ?string $firstField = NULL;

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

    public function __toString()
    {
        return $this->getName();
    }

    public function fetchFirstField(PDO $pdo): self
    {
        $queryFields = sprintf("DESCRIBE %s;", $this->name);
        $preResult = $pdo->prepare($queryFields);
        $preResult->execute();
        $firstOccurrence = $preResult->fetch(PDO::FETCH_NUM);
        $this->firstField = firstOccurrence[0];
        return $this;
    }

    public function getFirstField(): string
    {
        return $this->firstField;
    }
}
