<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "InventoryMetricsDto",
    title: "Métricas de Inventario",
    required: ["totalProductos", "stockTotal", "stockBajo", "productosInactivos"]
)]
class InventoryMetricsDto
{
    #[OA\Property(property: "totalProductos", type: "integer", example: 48)]
    public int $totalProductos;

    #[OA\Property(property: "stockTotal", type: "integer", example: 320)]
    public int $stockTotal;

    #[OA\Property(property: "stockBajo", type: "integer", example: 3)]
    public int $stockBajo;

    #[OA\Property(property: "productosInactivos", type: "integer", example: 2)]
    public int $productosInactivos;
}
