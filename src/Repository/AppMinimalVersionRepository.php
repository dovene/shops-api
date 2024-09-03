<?php

namespace App\Repository;

use App\Entity\AppMinimalVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AppMinimalVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppMinimalVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppMinimalVersion[]    findAll()
 * @method AppMinimalVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppMinimalVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppMinimalVersion::class);
    }
}
