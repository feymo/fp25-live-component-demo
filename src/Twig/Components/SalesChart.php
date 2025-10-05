<?php

namespace App\Twig\Components;

use App\Entity\Sale;
use App\Repository\SaleRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class SalesChart
{
    use DefaultActionTrait;

    #[LiveProp(updateFromParent: true)]
    public string $query = '';

    #[LiveProp(updateFromParent: true)]
    public string $status = '';

    #[LiveProp(updateFromParent: true)]
    public string $period = 'all';

    #[LiveProp(updateFromParent: true)]
    public int $totalResults = 0;

    public function __construct(
        private readonly SaleRepository $saleRepository,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @return Sale[]
     */
    private function getFilteredSales(): array
    {
        return $this->saleRepository->findByQueryWithOrderBy(
            $this->query,
            period: $this->period,
            status: $this->status
        );
    }

    public function getChart(): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData($this->getChartData());

        return $chart;
    }

    public function getChartData(): array
    {
        $sales = $this->getFilteredSales();

        $salesByMonth = [];
        foreach ($sales as $sale) {
            $month = $sale->getDate()?->format('Y-m');
            if (!isset($salesByMonth[$month])) {
                $salesByMonth[$month] = 0;
            }
            $salesByMonth[$month] += $sale->getAmount();
        }

        ksort($salesByMonth);

        $labels = [];
        $data = [];

        foreach ($salesByMonth as $month => $amount) {
            $labels[] = date('M Y', strtotime($month . '-01'));
            $data[] = round($amount, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ventes (€)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(76, 175, 80, 0.2)',
                    'borderColor' => 'rgba(76, 175, 80, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ]
            ]
        ];
    }

    public function getTotalAmount(): float
    {
        return array_sum(array_map(
            static fn($item) => $item->getAmount(),
            $this->getFilteredSales()
        ));
    }

    public function getSalesCount(): int
    {
        return $this->totalResults;
    }
}
