<?php

namespace Danilocgsilva\DatabaseDiscover;

use PDO;
use Generator;
use Danilocgsilva\DatabaseDiscover\{
    Table, 
    ReferencingTable, 
    Field
};

class DatabaseDiscover
{
    private PDO $pdo;

    public function setPdo(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * @return Generator, beign the fields from a table
     */
    public function getFieldsFromTable(string $tableName): Generator
    {
        $queryBase = "DESCRIBE $tableName";
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            $field = (new Field())
                ->setName($row["Field"])
                ->setType($row["Type"])
                ->seyNull($row["Null"])
                ->setKey($row["Key"])
                ->setDefault($row["Default"])
                ->setExtra($row["Extra"]);

            yield $field;
        }
    }

    /**
     * Get a list of all tables from the current database.
     * 
     * @return Generator A generator object containing Table objects with the table name set.
     */
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

    /**
     * Returns a generator object which yields Table objects containing the name and size of tables in the current database.
     * 
     * @return Generator Returns a generator object which yields Table objects containing the name and size of tables in the current database.
     */
    public function getTablesWithSize(): Generator
    {
        $queryBase = sprintf("SELECT TABLE_NAME, DATA_LENGTH+INDEX_LENGTH as FULL_SIZE FROM information_schema.tables WHERE table_schema = '%s'", $this->pdo->query('SELECT database()')->fetchColumn());
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            $table = new Table();
            $table->setName($row['TABLE_NAME']);
            $table->setSize($row['FULL_SIZE']);
            yield $table;
        }
    }

    /**
     * Provides all tables referencing the provided table
     * 
     * @param string $tableName The name of the table to reference.
     * 
     * @return Generator Returns all tables that references the provided table
     */
    public function referenceMe(string $tableName): Generator
    {
        $queryBase = sprintf("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` " .
            " WHERE REFERENCED_TABLE_NAME = '%s';", 
            $tableName
        );
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            yield new ReferencingTable(
                $row['TABLE_NAME'],
                $row['COLUMN_NAME'],
                $row['REFERENCED_COLUMN_NAME']
            );
        }
    }
}