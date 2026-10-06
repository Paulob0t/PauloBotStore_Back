<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MovementDetailDto",
    title: "Detalle de Producto en Venta",
    required: ["id_detalle", "id_producto", "nombre_producto", "cantidad", "precio_unitario", "total"]
)]
class MovementDetailDto
{
    #[OA\Property(property: "id_detalle", type: "integer", example: 1)]
    public int $id_detalle;

    #[OA\Property(property: "id_producto", type: "integer", example: 10)]
    public int $id_producto;

    #[OA\Property(property: "nombre_producto", type: "string", example: "Coca Cola 600ml")]
    public string $nombre_producto;

    #[OA\Property(property: "sku", type: "string", nullable: true, example: "BEB-001")]
    public ?string $sku;

    #[OA\Property(property: "descripcion", type: "string", nullable: true)]
    public ?string $descripcion;

    #[OA\Property(property: "cantidad", type: "integer", example: 2)]
    public int $cantidad;

    #[OA\Property(property: "precio_unitario", type: "number", format: "float", example: 25.00)]
    public float $precio_unitario;

    #[OA\Property(property: "descuento_unitario", type: "number", format: "float", example: 0.00)]
    public float $descuento_unitario;

    #[OA\Property(property: "subtotal", type: "number", format: "float", example: 50.00)]
    public float $subtotal;

    #[OA\Property(property: "iva_unitario", type: "number", format: "float", example: 0.00)]
    public float $iva_unitario;

    #[OA\Property(property: "total", type: "number", format: "float", example: 50.00)]
    public float $total;

    #[OA\Property(property: "notas_producto", type: "string", nullable: true)]
    public ?string $notas_producto;
}
