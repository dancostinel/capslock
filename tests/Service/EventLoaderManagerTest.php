<?php

namespace App\Tests\Service;

use App\Contract\EventLockInterface;
use App\Contract\EventSourceInterface;
use App\Dto\EventDto;
use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\EventLoaderManager;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

class EventLoaderManagerTest extends MockeryTestCase
{
    private $entityManager;
    private $logger;
    private $eventLoaderManager;
    private $eventSource;
    private $eventLock;
    private $eventRepository;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->eventSource = \Mockery::mock(EventSourceInterface::class);
        $this->eventLock = \Mockery::mock(EventLockInterface::class);
        $this->eventRepository = \Mockery::mock(EventRepository::class);
        $this->eventLoaderManager = new EventLoaderManager(
            $this->entityManager,
            $this->logger,
            $this->eventSource,
            $this->eventLock,
            $this->eventRepository
        );
    }

    public function testLoadEventsSuccess(): void
    {
        $sourceNames = ['source1'];
        $this->eventLock
            ->expects()
            ->acquire('source1', 30)
            ->andReturn(true);

        $this->eventRepository
            ->expects()
            ->getLastEventId('source1')
            ->andReturn(1);

        $eventDto = new EventDto(1, ['test1', 'test2', 'test3'], 'source1');
        $this->eventSource
            ->expects()
            ->fetchEvents('source1', 1)
            ->andReturn([$eventDto]);

        $this->eventLock
            ->expects()
            ->release('source1');

        $event = new Event()
            ->setId(1)
            ->setName('source1')
            ->setData('["test1","test2","test3"]');
        $this->entityManager
            ->expects()
            ->persist(\Mockery::isEqual($event));

        $this->logger
            ->expects()
            ->info('Stored 1 events for source source1');

        $this->entityManager
            ->expects()
            ->flush();

        $this->eventLoaderManager->loadEvents($sourceNames);
    }

    public function testLoadEventsLockAlreadyAcquired(): void
    {
        $sourceNames = ['source2'];
        $this->eventLock
            ->expects()
            ->acquire('source2', 30)
            ->andReturn(false);
        $this->logger
            ->expects()
            ->warning('Source source2 is locked by another instance');
        $this->eventRepository
            ->expects()
            ->getLastEventId('source2')
            ->never();

        $this->eventLoaderManager->loadEvents($sourceNames);
    }

    public function testLoadEventsThrowsException(): void
    {
        $sourceNames = ['source3'];
        $this->eventLock
            ->expects()
            ->acquire('source3', 30)
            ->andReturn(true);
        $this->eventRepository
            ->expects()
            ->getLastEventId('source3')
            ->andReturn(2);
        $this->eventSource
            ->expects()
            ->fetchEvents('source3', 2)
            ->andThrow(new \Exception('test exception message'));
        $this->eventLock
            ->expects()
            ->release('source3');
        $this->logger
            ->expects()
            ->error('Failed to process source source3: test exception message');
        $this->entityManager
            ->expects()
            ->flush()
            ->never();

        $this->eventLoaderManager->loadEvents($sourceNames);
    }

    public function testLoadEventsWithMultipleEvents(): void
    {
        $sourceNames = ['source4', 'source5', 'source6', 'source7', 'source8'];
        $this->getMocksAcquireLockForSources($sourceNames);
        $this->getMocksLastEventIdForSources($sourceNames);

        $eventDto4 = new EventDto(1, ['testing1', 'testing2', 'testing3'], 'source4');

        $resource = [];
        $resource['test'] = &$resource;
        $eventDto8 = new EventDto(4, $resource, 'source8');

        $this->getMocksEventsAndReleaseLocks($eventDto4, $eventDto8);
        $this->getMocksEventsLoggerMessages();

        $event4 = new Event()
            ->setId(1)
            ->setName('source4')
            ->setData('["testing1","testing2","testing3"]');
        $this->entityManager
            ->expects()
            ->persist(\Mockery::isEqual($event4));

        $this->entityManager
            ->expects()
            ->flush();

        $this->eventLoaderManager->loadEvents($sourceNames);
    }

    private function getMocksAcquireLockForSources(array $sourceNames): void
    {
        foreach ($sourceNames as $sourceName) {
            if ('source5' === $sourceName) {
                $this->eventLock
                    ->expects()
                    ->acquire($sourceName, 30)
                    ->andReturn(false);
                continue;
            }

            $this->eventLock
                ->expects()
                ->acquire($sourceName, 30)
                ->andReturn(true);
        }
    }

    private function getMocksLastEventIdForSources(array $sourceNames): void
    {
        $id = 1;
        foreach ($sourceNames as $sourceName) {
            if ('source5' === $sourceName) {
                continue;
            }
            $this->eventRepository
                ->expects()
                ->getLastEventId($sourceName)
                ->andReturn($id);
            $id++;
        }
    }

    private function getMocksEventsAndReleaseLocks(EventDto $eventDto4, EventDto $eventDto8): void
    {
        $this->eventSource
            ->expects()
            ->fetchEvents('source4', 1)
            ->andReturn([$eventDto4]);
        $this->eventLock
            ->expects()
            ->release('source4');
        $this->eventSource
            ->expects()
            ->fetchEvents('source6', 2)
            ->andThrow(new \Exception('test exception message6'));
        $this->eventLock
            ->expects()
            ->release('source6');
        $this->eventSource
            ->expects()
            ->fetchEvents('source7', 3)
            ->andReturn([]);
        $this->eventLock
            ->expects()
            ->release('source7');
        $this->eventSource
            ->expects()
            ->fetchEvents('source8', 4)
            ->andReturn([$eventDto8]);
        $this->eventLock
            ->expects()
            ->release('source8');
    }

    private function getMocksEventsLoggerMessages(): void
    {
        $this->logger
            ->expects()
            ->info('Stored 1 events for source source4');
        $this->logger
            ->expects()
            ->warning('Source source5 is locked by another instance');
        $this->logger
            ->expects()
            ->error('Failed to process source source6: test exception message6');
        $this->logger
            ->expects()
            ->info('No new events for source source7');
        $this->logger
            ->expects()
            ->error('Failed to process source source8: Failed to encode event data');
    }
}
