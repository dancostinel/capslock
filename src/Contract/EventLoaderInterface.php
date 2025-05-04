<?php

namespace App\Contract;

interface EventLoaderInterface
{
    /**
     * Loads events from multiple sources
     */
    public function loadEvents(array $sourceNames): void;
}
