<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

class ReferencingTable
{
    public function __construct(
        private string $referencingTable,
        private string $localTableField,
        private string $referencingId
    ) { }

    public function getReferencingTable(): string
    {
        return $this->referencingTable;
    }

    public function getLocalTableField(): string
    {
        return $this->localTableField;
    }

    public function getReferencingId(): string
    {
        return $this->referencingId;
    }
}
