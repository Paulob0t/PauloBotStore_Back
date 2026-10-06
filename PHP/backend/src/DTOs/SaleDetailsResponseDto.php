<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SaleDetailsResponseDto",
    title: "Desglose de Venta Completa",
    required: ["id_comanda", "folio", "fecha_venta", "total", "productos"]
)]
class SaleDetailsResponseDto
{
    #[OA\Property(property: "id_comanda", type: "integer", example: 34)]
    public int $id_comanda;

    #[OA\Property(property: "folio", type: "string", example: "VENTA-20260902-001")]
    public string $folio;

    #[OA\Property(property: "fecha_venta", type: "string", example: "2026-09-02 14:30:00")]
    public string $fecha_venta;

    #[OA\Property(property: "subtotal", type: "number", format: "float", example: 100.00)]
    public float $subtotal;

    #[OA\Property(property: "total", type: "number", format: "float", example: 100.00)]
    public float $total;

    #[OA\Property(property: "metodo_pago", type: "string", example: "Efectivo")]
    public string $metodo_pago;

    #[OA\Property(property: "notas", type: "string", nullable: true)]
    public ?string $notas;

    #[OA\Property(
        property: "productos",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/MovementDetailDto")
    )]
    public array $productos;
}
