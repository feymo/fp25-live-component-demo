<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SaleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    private const int ITEMS_PER_PAGE = 15;

    #[Route('/', name: 'app_dashboard', methods: [Request::METHOD_GET])]
    public function index(Request $request, SaleRepository $saleRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        $sales = $saleRepository->findAllOrderedByDate($page, self::ITEMS_PER_PAGE);
        $totalSales = $saleRepository->countAll();
        $totalPages = (int) ceil($totalSales / self::ITEMS_PER_PAGE);
        $stats = $saleRepository->getStats();

        return $this->render('dashboard/index.html.twig', [
            'sales' => $sales,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalSales' => $totalSales,
            'itemsPerPage' => self::ITEMS_PER_PAGE,
        ]);
    }
}
