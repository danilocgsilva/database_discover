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

    /**
     * Fields are returned as string
     */
    public function getFieldsFromTable(string $tableName): Generator
    {
        $queryBase = "DESCRIBE $tableName";
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            yield $row["Field"];
        }
    }

    public function showTables(): Generator
    {
        $queryBase = sprintf("SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND TABLE_SCHEMA = '%s';", $this->pdo->query('SELECT database()')->fetchColumn());
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            yield $row['table_name'];
        }
    }
}