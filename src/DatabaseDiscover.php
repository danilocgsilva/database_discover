<?php

namespace Danilocgsilva\DatabaseDiscover;

use PDO;
use Generator;

class DatabaseDiscover
{
    private PDO $pdo;

    public function setPdo(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    public function getFieldsFromTable(string $tableName): Generator
    {
        $queryBase = "DESCRIBE $tableName";
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            yield $row["Field"];
        }
    }
}