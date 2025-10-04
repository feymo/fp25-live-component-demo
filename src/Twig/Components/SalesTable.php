<?php

namespace App\Twig\Components;

use App\Repository\SaleRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(template: 'components/SalesTable.html.twig')]
class SalesTable
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public string $sortBy = 'id';

    #[LiveProp(writable: true)]
    public string $sortDir = 'asc';

    #[LiveProp(writable: true, url: true)]
    public string $status = '';

    #[LiveProp(writable: true, url: true)]
    public string $period = 'all';

    private const int ITEMS_PER_PAGE = 15;

    public function __construct(
        private readonly SaleRepository $saleRepository,
    ) {
    }

    public function getSales(): array
    {
        return $this->saleRepository->findByQueryWithOrderBy(
            $this->query,
            $this->sortBy,
            $this->sortDir,
            $this->period,
            $this->status
        );
    }

    public function getPaginatedSales(): array
    {
        $allSales = $this->getSales();
        $offset = ($this->page - 1) * self::ITEMS_PER_PAGE;

        return array_slice($allSales, $offset, self::ITEMS_PER_PAGE);
    }

    public function getTotalPages(): int
    {
        $total = count($this->getSales());
        return (int) ceil($total / self::ITEMS_PER_PAGE);
    }

    public function getTotalCount(): int
    {
        return count($this->getSales());
    }

    public function getTotalAmount(): float
    {
        return array_sum(array_column($this->getSales(), 'amount'));
    }

    public function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'paid' => 'badge-success',
            'pending' => 'badge-warning',
            'cancelled' => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    public function getStatusLabel(string $status): string
    {
        return match($status) {
            'paid' => 'Payé',
            'pending' => 'En attente',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }

    public function getStats(): array
    {
        return $this->saleRepository->getStats();
    }

    #[LiveAction]
    public function sort(#[LiveArg]string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->query = '';
        $this->status = '';
        $this->period = 'all';
        $this->sortBy = 'date';
        $this->sortDir = 'desc';
    }

    public function getSortIcon(string $column): string
    {
        if ($this->sortBy !== $column) {
            return '↕️';
        }

        return $this->sortDir === 'asc' ? '⬆️' : '⬇️';
    }

    public function isSortedBy(string $column): bool
    {
        return $this->sortBy === $column;
    }

    public function getStatusCount(string $status): int
    {
        return count(array_filter($this->getSales(), static fn($sale) => $sale['status'] === $status));
    }

    public function hasActiveFilters(): bool
    {
        return $this->query !== ''
            || $this->status !== ''
            || $this->period !== 'all';
    }
}
