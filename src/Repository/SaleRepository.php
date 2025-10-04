<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Sale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sale>
 */
final class SaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sale::class);
    }

    public function findAllOrderedByDate(int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('s')
            ->orderBy('s.date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByQuery(string $query, int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('s')
            ->where('s.client LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByQuery(string $query): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.client LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('s');

        return [
            'total' => $qb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult(),
            'totalAmount' => $qb->select('SUM(s.amount)')->getQuery()->getSingleScalarResult(),
            'averageAmount' => $qb->select('AVG(s.amount)')->getQuery()->getSingleScalarResult(),
        ];
    }
}
