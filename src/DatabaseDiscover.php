<?php

namespace Danilocgsilva\DatabaseDiscover;

use PDO;
use Generator;
use Danilocgsilva\DatabaseDiscover\Table;

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

    public function getTables(): Generator
    {
        $queryBase = sprintf("SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND TABLE_SCHEMA = '%s';", $this->pdo->query('SELECT database()')->fetchColumn());
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            $table = new Table();
            $table->setName($row['table_name']);
            yield $table;
        }
    }

    public function getTablesWithSize(): Generator
    {
        $queryBase = "SELECT TABLE_NAME, DATA_LENGTH+INDEX_LENGTH as FULL_SIZE FROM information_schema.tables WHERE table_schema = 'inoserver_prod'";
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            $table = new Table();
            $table->setName($row['TABLE_NAME']);
            $table->setSize($row['FULL_SIZE']);
            yield $table;
        }
    }
}