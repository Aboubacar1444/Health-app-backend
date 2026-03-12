<?php

namespace App\Repository;

use App\Entity\DevicePushToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DevicePushToken>
 */
class DevicePushTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DevicePushToken::class);
    }

    /**
     * @return DevicePushToken[]
     */
    public function findActiveTokensByUserId(Uuid $userId): array
    {
        return $this->createQueryBuilder('dpt')
            ->where('dpt.userId = :userId')
            ->andWhere('dpt.isActive = :active')
            ->setParameter('userId', $userId, 'uuid')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }
}
