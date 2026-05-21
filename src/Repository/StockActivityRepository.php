<?php

namespace App\Repository;

use App\Entity\StockActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockActivity>
 */
class StockActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockActivity::class);
    }

    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.product = :product')
            ->setParameter('product', $product)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentActivities(int $limit = 50): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
