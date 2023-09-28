<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;

class QueryCache
{
    private string $baseKey;
    private $toQuery;

    public function __construct(private PDO $pdo, private string $query)
    { 
        $this->baseKey = preg_replace(
            ["/\//", "/:/", "/\?/", "/=/", "/&/", "/-/"], 
            "_", 
            $query
        );
    }

    public function fetch()
    {
        // $toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        // $toQuery->execute();
        // while ($row = $toQuery->fetch(PDO::FETCH_ASSOC)) {
        //     yield $row;
        // }
        return $this->toQuery->fetch(PDO::FETCH_ASSOC);
    }

    public function execute()
    {
        $this->toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $this->toQuery->execute();
    }
}
