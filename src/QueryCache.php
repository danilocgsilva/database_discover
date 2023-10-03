<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;

class QueryCache extends Cache
{
    private string $baseKey;

    private $toQuery = null;

    private ?bool $shouldQuery;

    private int $cursor = 0;

    public function __construct(
        private PDO $pdo, 
        private string $query,
        string $cacheSuffix = null
    ) { 
        parent::__construct();
        $this->baseKey = preg_replace(
            [
                "/\//", "/:/",  "/\?/",
                "/=/",  "/&/",  "/-/" ,
                "/ /",  "/'/",  "/;/" ,
                "/,/",  "/\+/", "/\*/",
                "/\)/", "/\(/"
            ], 
            "", 
            $query
        );

        if ($cacheSuffix) {
            $this->baseKey .= $cacheSuffix;
        }

        $cacheKey = $this->baseKey;
        $cachedData = $this->storage->getItem($cacheKey);
        if (!$cachedData->isHit()) {
            $cachedData->set('shoud not query them');
            $cachedData->expiresAfter($this->cacheSecondsTime);
            $this->storage->save($cachedData);
            $this->shouldQuery = true;
        } else {
            $this->shouldQuery = false;
        }
    }

    public function fetch(): array|false
    {
        $cacheKey = (string) $this->cursor . "_" . $this->baseKey;
        $cachedData = $this->storage->getItem($cacheKey);
        if (!$cachedData->isHit()) {

            if (!$this->toQuery) {
                return false;
            }

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
        if ($this->shouldQuery) {
            $this->toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $this->toQuery->execute();
        }
    }
}
