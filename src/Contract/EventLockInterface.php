<?php

namespace App\Contract;

interface EventLockInterface
{
    /**
     * Acquires a lock for a source
     * @return true if acquired
     * @return false if already locked
     */
    public function acquire(string $sourceName, int $ttlSeconds): bool;

    /**
     * Releases a lock for a source
     */
    public function release(string $sourceName): void;
}
