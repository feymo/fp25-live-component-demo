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

    public function findByQueryWithOrderBy(
        string $query = '',
        string $sortBy = 'id',
        string $sortOrder = 'asc',
        string $period = 'all',
        string $status = ''
    ): array {
        $queryBuilder = $this->createQueryBuilder('s')
            ->orderBy(\sprintf('s.%s', $sortBy), $sortOrder);

        if ('' !== $query) {
            $queryBuilder
                ->where('s.client LIKE :query')
                ->setParameter('query', \sprintf('%%%s%%', $query));
        }

        if ($period !== 'all') {
            $now = new \DateTime();
            $startDate = match($period) {
                'today' => (clone $now)->setTime(0, 0),
                'week' => (clone $now)->modify('-7 days'),
                'month' => (clone $now)->modify('-1 month'),
                'quarter' => (clone $now)->modify('-3 months'),
                default => null,
            };
            $queryBuilder
                ->andWhere('s.date >= :start')
                ->setParameter('start', $startDate);
        }

        if ($status !== '') {
            $queryBuilder
                ->andWhere('s.status = :status')
                ->setParameter('status', $status);
        }

        return $queryBuilder->getQuery()->getResult();
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
