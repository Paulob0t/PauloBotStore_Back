<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "RecentSaleDto", title: "Venta Reciente")]
class RecentSaleDto
{
    #[OA\Property(property: "folio", type: "string", example: "FOL-10492")]
    public string $folio;

    #[OA\Property(property: "fecha_venta", type: "string", example: "2026-09-01 14:15:00")]
    public string $fecha_venta;

    #[OA\Property(property: "total", type: "number", format: "float", example: 45.00)]
    public float $total;

    #[OA\Property(property: "metodo_pago", type: "string", nullable: true, example: "Efectivo")]
    public ?string $metodo_pago;

    #[OA\Property(property: "tipo_pago", type: "string", nullable: true, example: "MDB Monedas")]
    public ?string $tipo_pago;
}
