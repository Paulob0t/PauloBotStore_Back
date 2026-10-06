<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: "TopProductDto", title: "Producto Más Vendido")]
class TopProductDto
{
    #[OA\Property(property: "nombre_producto", type: "string", example: "Coca Cola 600ml")]
    public string $nombre_producto;

    #[OA\Property(property: "unidades", type: "integer", example: 45)]
    public int $unidades;

    #[OA\Property(property: "ingresos", type: "number", format: "float", example: 900.00)]
    public float $ingresos;
}
