<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getLastEventId(string $sourceName): int
    {
        $result = $this->createQueryBuilder('event')
            ->select('MAX(event.id)')
            ->where('event.sourceName = :sourceName')
            ->setParameter('sourceName', $sourceName)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result[0] ?? 0);
    }
}
