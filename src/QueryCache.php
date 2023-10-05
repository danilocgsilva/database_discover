<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use PDO;
use Generator;
use Danilocgsilva\Database\Traits\Fetch;

class QueryCache extends Cache
{
    use Fetch;
    
    private string $baseKey;

    private $toQuery = null;

    private ?bool $shouldQuery;

    private bool $skipCache = false;

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

    public function skipCache(): void
    {
        $this->skipCache = true;
    }

    public function fetch(): array|false
    {
        if ($this->skipCache) {
            return $this->fetchWithoutCache();
        } else {
            return $this->fetchWithCache();
        }
    }

    public function execute()
    {
        if ($this->shouldQuery || $this->skipCache) {
            $this->toQuery = $this->pdo->prepare($this->query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $this->toQuery->execute();
        }
    }
}
