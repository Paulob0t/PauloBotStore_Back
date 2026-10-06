<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SalesMetricsDto",
    title: "Métricas de Ventas",
    required: ["ventasHoyCnt", "ventasHoyMonto", "ventasMesCnt", "ventasMesMonto", "promedioVenta", "fechaLabel"]
)]
class SalesMetricsDto
{
    #[OA\Property(property: "ventasHoyCnt", type: "integer", example: 12)]
    public int $ventasHoyCnt;

    #[OA\Property(property: "ventasHoyMonto", type: "number", format: "float", example: 450.50)]
    public float $ventasHoyMonto;

    #[OA\Property(property: "ventasMesCnt", type: "integer", example: 180)]
    public int $ventasMesCnt;

    #[OA\Property(property: "ventasMesMonto", type: "number", format: "float", example: 6850.00)]
    public float $ventasMesMonto;

    #[OA\Property(property: "promedioVenta", type: "number", format: "float", example: 38.05)]
    public float $promedioVenta;

    #[OA\Property(property: "fechaLabel", type: "string", example: "01/09/2026")]
    public string $fechaLabel;
}
