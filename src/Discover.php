<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;
use Exception;

class Discover
{
    private ?int $tableCount;

    public function __construct(private ?PDO $pdo = null) {}

    public function setPdo(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * @return Generator<Field>
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
                ->setNull($row["Null"])
                ->setKey($row["Key"])
                ->setDefault($row["Default"])
                ->setExtra($row["Extra"]);
            yield $field;
        }
    }

    /**
     * Get a list of all tables from the6 current database.
     * 
     * @return Generator<Table>
     */
    public function getTables(): Generator
    {
        $this->tableCount = 0;
        $queryBase = sprintf(
            "SELECT table_name as table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND TABLE_SCHEMA = '%s';", 
            $this->pdo->query('SELECT database()')->fetchColumn()
        );
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            $table = new Table();
            $table->setName($row['table_name']);
            yield $table;
            $this->tableCount++;
        }
    }

    /**
     * Returns a generator object which yields Table objects containing the name and size of tables in the current database.
     * 
     * @return Generator<Table>
     */
    public function getTablesWithSize(): Generator
    {
        $queryBase = sprintf(
            "SELECT TABLE_NAME, DATA_LENGTH+INDEX_LENGTH as FULL_SIZE FROM information_schema.tables WHERE table_schema = '%s'", 
            $this->pdo->query('SELECT database()')->fetchColumn()
        );
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
     * Get table size in bytes
     * 
     * @return int
     */
    public function getTableSize(string $tableName): int
    {
        $queryBase = sprintf(
            "SELECT TABLE_NAME, DATA_LENGTH+INDEX_LENGTH as FULL_SIZE FROM information_schema.tables WHERE table_schema = '%s' AND TABLE_NAME = '%s'", 
            $this->pdo->query('SELECT database()')->fetchColumn(),
            $tableName
        );
        $toQuery = $this->pdo->prepare($queryBase, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        $row = $toQuery->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("No table found for {$tableName}");
        }
        return $row['FULL_SIZE'];
    }

    /**
     * The table rows count
     * 
     * return int
     */
    public function getRegistersCount(string $tableName): int
    {
        $queryBase = "SELECT COUNT(*) as registers_count FROM %s";
        $toQuery = $this->pdo->prepare(sprintf($queryBase, $tableName), [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        $row = $toQuery->fetch(PDO::FETCH_ASSOC);
        return $row['registers_count'];
    }

    /**
     * Provides the table counting.
     * 
     * NOTICE: table count is available only after getTables is used.
     *
     * @return integer
     */
    public function getTableCount(): int
    {
        return $this->tableCount;
    }

    /**
     * Provides all tables referencing the provided table
     * 
     * @param string $tableName The name of the table to reference.
     * 
     * @return Generator<ReferencingTable>
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

    /**
     * List tables that have the field name
     *
     * @param string $fieldName
     * @return Generator<Table>
     */
    public function tablesWithEqualFieldName(string $fieldName): Generator
    {
        foreach ($this->getTables() as $table) {
            foreach (
                $this->getFieldsFromTable($table->getName()) as $field
            ) {
                if ($field->getName() === $fieldName) {
                    yield $table;
                }
            }
        }
    }
}