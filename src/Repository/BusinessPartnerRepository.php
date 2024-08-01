<?php

namespace App\Repository;

use App\Entity\BusinessPartner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusinessPartner>
 * @method BusinessPartner|null find($id, $lockMode = null, $lockVersion = null)
 * @method BusinessPartner|null findOneBy(array $criteria, array $orderBy = null)
 * @method BusinessPartner[]    findAll()
 * @method BusinessPartner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BusinessPartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessPartner::class);
    }
}
