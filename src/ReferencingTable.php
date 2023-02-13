<?php

namespace Danilocgsilva\DatabaseDiscover;

use PDO;
use Generator;
use Danilocgsilva\DatabaseDiscover\Table;

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
