<?php

namespace App\Contract;

use App\Dto\EventDto;

interface EventSourceInterface
{
    /**
     * Fetches events from the source
     * @return EventDto[] List of events sorted by ID, up to 1000.
     * @throws \Exception On network or server errors.
     */
    public function fetchEvents(string $source, int $lastFetchedId): array;
}
