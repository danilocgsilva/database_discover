<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;
use PDOException;
use Exception;

class Discover
{
    private ?int $tableCount;

    private $pdo;

    private array $skipTables = [];

    private $logMessages;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo === null) {
            $this->pdo = new class() {
                public function __call($name, $arguments)
                {
                    throw new Exception("You forgot to assing a PDO to the class.");
                }
            };
        } else {
            $this->pdo = $pdo;
        }
    }

    public function setLogMessages($logMessages): self{
        $this->logMessages = $logMessages;
        return $this;
    }

    /**
     * Set tables to ignore.
     * 
     * @param array $skipTables
     * 
     * @return self
     */
    public function setSkipTables(array $skipTables): self
    {
        $this->skipTables = $skipTables;
        return $this;
    }

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
        if ($this->logMessages) {
            $this->logMessages->message("Fetching fields for table " . $tableName);
        }
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
     * Get a list of all tables from the current database.
     * 
     * @return Table[]
     */
    public function getTables(): array
    {
        $this->tableCount = 0;
        $queryBase = sprintf(
            "SELECT table_name as table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND TABLE_SCHEMA = '%s';", 
            $this->pdo->query('SELECT database()')->fetchColumn()
        );
        if ($this->logMessages) {
            $this->logMessages->message("Query to fetch tables: " . $queryBase);
        }
        $queryCache = new QueryCache(
            $this->pdo, 
            $queryBase, 
            hash('md5', $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS))
        );
        $queryCache->execute();
        $tables = [];
        while ($row = $queryCache->fetch()) {
            if ($this->logMessages) {
                $this->logMessages->message("Discovered table " . ($tableName = $row['table_name']));
            }
            if (!$this->nameCompliant($tableName)) {
                if ($this->logMessages) {
                    $this->logMessages->message("WARNNING! Table name is not compliant: " . $tableName . ".");
                }
                continue;
            }
            $table = new Table();
            $table->setName($tableName);
            $tables[] = $table;
            $this->tableCount++;
        }

        return $tables;
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
        $queryCache = new QueryCache(
            $this->pdo, 
            $queryBase, 
            hash('md5', $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS))
        );
        $queryCache->execute();

        $row = $queryCache->fetch();
        if (!$row) {
            throw new TableNotFoundException("No table found for {$tableName}");
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
        $queryBase = sprintf("SELECT COUNT(*) as registers_count FROM %s", $tableName);

        $queryCache = new QueryCache(
            $this->pdo, 
            $queryBase, 
            hash('md5', $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS))
        );

        try {
            $queryCache->execute();
            $row = $queryCache->fetch();
            return $row['registers_count'] ?? 0;
        } catch (PDOException $e) {
            return 0;
        }
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
            if (
                in_array((string) $table, $this->skipTables)
            ) {
                continue;
            }
            
            foreach (
                $this->getFieldsFromTable($table->getName()) as $field
            ) {
                if ($field->getName() === $fieldName) {
                    yield $table;
                }
            }
        }
    }

    public function getCreateTableReceipt(?string $tableName): string
    {
        $queryBase = 'SHOW CREATE TABLE %s;';        
        if ($tableName) {
            $query = sprintf($queryBase, $tableName);
            $preResult = $this->pdo->prepare($query);
            $preResult->execute();
            $results = $preResult->fetch(PDO::FETCH_NUM);
            return $results[1];
        }

        $createTableString = "";
        foreach($this->getTables() as $table) {
            $query = sprintf($queryBase, $table->getName());
            $preResult = $this->pdo->prepare($query);
            $preResult->execute();
            $rowData = $preResult->fetch(PDO::FETCH_NUM);
            $createTableString .= $rowData[1];
        }
        return $createTableString;
    }

    private function nameCompliant(string $tableName): bool
    {
        if (preg_match("/'/", $tableName)) {
            return false;
        }
        if (preg_match("/\./", $tableName)) {
            return false;
        }
        return true;
    }
}
