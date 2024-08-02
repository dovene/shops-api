<?php

namespace App\Repository;

use App\Entity\EventItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventItem>
 *
 * @method EventItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventItem[]    findAll()
 * @method EventItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventItem::class);
    }

    // Custom repository methods (if needed) go here
}
