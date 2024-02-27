<?php

declare(strict_types=1);

namespace Danilocgsilva\Database;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

abstract class Cache
{
    protected $storage;

    /**
     * Interval for cache expiration
     *
     * @var integer
     */
    protected int $cacheSecondsTime = 86400;

    public function __construct()
    {
        $this->buildStorage();
    }

    /**
     * Changes the default cache expiration time in seconds
     *
     * @param integer $cacheSecondsTime
     * @return self
     */
    public function setCacheSecondsTime(int $cacheSecondsTime): self
    {
        $this->cacheSecondsTime = $cacheSecondsTime;
        return $this;
    }

    private function buildStorage()
    {
        $this->storage = new FilesystemAdapter();
    }

}