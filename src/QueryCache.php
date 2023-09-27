<?php

declare(strict_types=1);

class QueryCache
{
    private string $baseKey;

    public function __construct(private PDO $pdo, private string $query)
    { 
        $this->baseKey = preg_replace(
            ["/\//", "/:/", "/\?/", "/=/", "/&/", "/-/"], 
            "_", 
            $query
        );
    }

    public function fetch(): Generator
    {
        $toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $toQuery->execute();
        while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }
}
