<?php

namespace App\Twig\Components;

use App\Repository\SaleRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
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

    private const int ITEMS_PER_PAGE = 15;

    public function __construct(
        private readonly SaleRepository $saleRepository,
    ) {
    }

    public function getSales(): array
    {
        $allSales = $this->saleRepository->findAll();
        if ('' === $this->query) {
            return $allSales;
        }

        $sales = array_filter($allSales, function($sale) {
            return stripos($sale->getClient(), $this->query) !== false;
        });

        return array_values($sales);
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
}
