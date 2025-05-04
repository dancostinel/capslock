<?php

namespace App\Service;

use App\Contract\EventLoaderInterface;
use App\Contract\EventLockInterface;
use App\Contract\EventSourceInterface;
use App\Dto\EventDto;
use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EventLoaderManager implements EventLoaderInterface
{
    private const int REQUEST_INTERVAL_MS = 200 * 1000; // 200ms between requests to the same source
    private const int LOCK_TTL_SECONDS = 30; // Lock TTL to prevent deadlocks
    private bool $shouldFlush = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly EventSourceInterface $eventSource,
        private readonly EventLockInterface $eventLock,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function loadEvents(array $sourceNames): void
    {
        foreach ($sourceNames as $sourceName) {
            try {
                $this->processSource($sourceName);
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to process source ' . $sourceName . ': ' . $exception->getMessage());
                continue;
            }
        }

        if ($this->shouldFlush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @throws \Exception
     */
    private function processSource(string $sourceName): void
    {
        if (!$this->eventLock->acquire($sourceName, self::LOCK_TTL_SECONDS)) {
            $this->logger->warning('Source ' . $sourceName . ' is locked by another instance');

            return;
        }

        $lastEventId = $this->eventRepository->getLastEventId($sourceName);
        try {
            $events = $this->eventSource->fetchEvents($sourceName, $lastEventId);
        } finally {
            $this->eventLock->release($sourceName);
        }

        if (empty($events)) {
            $this->logger->info('No new events for source '. $sourceName);

            return;
        }

        $this->storeEvents($events);
        $this->logger->info('Stored ' . count($events) . ' events for source ' . $sourceName);

        usleep(self::REQUEST_INTERVAL_MS);
    }

    /**
     * @throws \RuntimeException
     */
    private function storeEvents(array $events): void
    {
        /** @var EventDto $eventDto */
        foreach ($events as $eventDto) {
            $data = json_encode($eventDto->getData());
            if (empty($data)) {
                throw new \RuntimeException('Failed to encode event data');
            }

            $event = new Event()
                ->setId($eventDto->getId())
                ->setName($eventDto->getSourceName())
                ->setData($data);

            $this->entityManager->persist($event);
            $this->shouldFlush = true;
        }
    }
}
