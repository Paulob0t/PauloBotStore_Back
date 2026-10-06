<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MovementsSummaryDto",
    title: "Resumen de Métricas de Movimientos",
    required: ["totalVentas", "totalIngresos", "ventasHoy", "promedioVenta"]
)]
class MovementsSummaryDto
{
    #[OA\Property(property: "totalVentas", type: "integer", example: 34)]
    public int $totalVentas;

    #[OA\Property(property: "totalIngresos", type: "number", format: "float", example: 4520.50)]
    public float $totalIngresos;

    #[OA\Property(property: "ventasHoy", type: "integer", example: 5)]
    public int $ventasHoy;

    #[OA\Property(property: "promedioVenta", type: "number", format: "float", example: 132.95)]
    public float $promedioVenta;
}
