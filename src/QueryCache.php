<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;

class QueryCache extends Cache
{
    private string $baseKey;

    private $toQuery;

    private int $cursor = 0;

    public function __construct(
        private PDO $pdo, 
        private string $query
    ) { 
        parent::__construct();
        $this->baseKey = preg_replace(
            ["/\//", "/:/", "/\?/", "/=/", "/&/", "/-/", "/ /", "/'/", "/;/"], 
            "_", 
            $query
        );
    }

    public function fetch(): array|false
    {
        $cacheKey = (string) $this->cursor . "_" . $this->baseKey;
        $cachedData = $this->storage->getItem($cacheKey);
        if (!$cachedData->isHit()) {
            $cursorData = $this->toQuery->fetch(PDO::FETCH_ASSOC);
            if (!$cursorData) {
                return false;
            }
            $cachedData->set($cursorData);
            $cachedData->expiresAfter($this->cacheSecondsTime);
            $this->storage->save($cachedData);
            $this->cursor++;
            return $cursorData;
        }

        if ($this->storage->hasItem($cacheKey)) {
            $retrievedData = $this->storage->getItem($cacheKey);
            $cursorData = $retrievedData->get();
            $this->cursor++;
            return $cursorData;
        }
    }

    public function execute()
    {
        $this->toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $this->toQuery->execute();
    }
}
