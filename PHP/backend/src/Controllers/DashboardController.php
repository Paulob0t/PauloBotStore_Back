<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\DashboardMetricsDto;
use App\Models\Dashboard;
use OpenApi\Attributes as OA;

class DashboardController
{
    private Dashboard $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new Dashboard();
    }

    #[OA\Get(
        path: "/api/v1/dashboard",
        operationId: "getDashboardMetrics",
        summary: "Obtener métricas y estadísticas del Dashboard",
        description: "Retorna el resumen consolidado de ventas de hoy, ventas del mes, inventario, tendencia de 7 días, top productos y últimas ventas.",
        tags: ["Dashboard"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Métricas del dashboard obtenidas exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/DashboardMetricsDto")
            ),
            new OA\Response(
                response: 500,
                description: "Error interno al consultar métricas",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getMetrics(): void
    {
        try {
            $sales = $this->dashboardModel->getSalesMetrics();
            $inventory = $this->dashboardModel->getInventoryMetrics();
            $chart = $this->dashboardModel->getSalesLast7DaysChart();
            $topProducts = $this->dashboardModel->getTopProducts(5);
            $recentSales = $this->dashboardModel->getRecentSales(6);

            Response::json([
                'sales' => $sales,
                'inventory' => $inventory,
                'chart' => $chart,
                'topProducts' => $topProducts,
                'recentSales' => $recentSales
            ], 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener datos del dashboard: ' . $e->getMessage(), null, 500);
        }
    }
}
