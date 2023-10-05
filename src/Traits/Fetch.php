<?php

declare(strict_types=1);

namespace Danilocgsilva\Database\Traits;

use PDO;


trait Fetch
{
    private function fetchWithoutCache()
    {
        if (!$this->toQuery) {
            return false;
        }

        $cursorData = $this->toQuery->fetch(PDO::FETCH_ASSOC);

        if (!$cursorData) {
            return false;
        }

        $this->cursor++;

        return $cursorData;
    }

    private function fetchWithCache()
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
}