<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "DashboardMetricsDto",
    title: "Resumen del Dashboard",
    required: ["sales", "inventory", "chart", "topProducts", "recentSales"]
)]
class DashboardMetricsDto
{
    #[OA\Property(property: "sales", ref: "#/components/schemas/SalesMetricsDto")]
    public SalesMetricsDto $sales;

    #[OA\Property(property: "inventory", ref: "#/components/schemas/InventoryMetricsDto")]
    public InventoryMetricsDto $inventory;

    #[OA\Property(property: "chart", ref: "#/components/schemas/ChartDto")]
    public ChartDto $chart;

    #[OA\Property(property: "topProducts", type: "array", items: new OA\Items(ref: "#/components/schemas/TopProductDto"))]
    public array $topProducts;

    #[OA\Property(property: "recentSales", type: "array", items: new OA\Items(ref: "#/components/schemas/RecentSaleDto"))]
    public array $recentSales;
}
