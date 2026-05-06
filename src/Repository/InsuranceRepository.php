<?php

namespace App\Repository;

use App\Entity\Insurance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Insurance>
 *
 * @method Insurance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Insurance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Insurance[]    findAll()
 * @method Insurance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InsuranceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Insurance::class);
    }
}
