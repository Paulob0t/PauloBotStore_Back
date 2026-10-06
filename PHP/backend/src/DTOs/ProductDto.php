<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ProductDto",
    title: "Detalle de Producto",
    required: ["id_producto", "nombre_producto", "precio", "stock", "ubicacion", "activo"]
)]
class ProductDto
{
    #[OA\Property(property: "id_producto", type: "integer", example: 10)]
    public int $id_producto;

    #[OA\Property(property: "id_categoria", type: "integer", nullable: true, example: 1)]
    public ?int $id_categoria;

    #[OA\Property(property: "id_subcategoria", type: "integer", nullable: true, example: 4)]
    public ?int $id_subcategoria;

    #[OA\Property(property: "nombre_categoria", type: "string", nullable: true, example: "Bebidas")]
    public ?string $nombre_categoria;

    #[OA\Property(property: "nombre_subcategoria", type: "string", nullable: true, example: "Refrescos")]
    public ?string $nombre_subcategoria;

    #[OA\Property(property: "nombre_producto", type: "string", example: "Coca Cola 600ml")]
    public string $nombre_producto;

    #[OA\Property(property: "descripcion", type: "string", nullable: true)]
    public ?string $descripcion;

    #[OA\Property(property: "precio", type: "number", format: "float", example: 25.00)]
    public float $precio;

    #[OA\Property(property: "descuento", type: "number", format: "float", nullable: true, example: 0.0)]
    public ?float $descuento;

    #[OA\Property(property: "stock", type: "integer", example: 24)]
    public int $stock;

    #[OA\Property(property: "sku", type: "string", nullable: true, example: "BEB-001")]
    public ?string $sku;

    #[OA\Property(property: "ubicacion", type: "string", example: "A1")]
    public string $ubicacion;

    #[OA\Property(property: "destacado", type: "integer", example: 1)]
    public int $destacado;

    #[OA\Property(property: "orden_destacado", type: "integer", nullable: true, example: 1)]
    public ?int $orden_destacado;

    #[OA\Property(property: "activo", type: "integer", example: 1)]
    public int $activo;

    #[OA\Property(property: "tiene_imagen", type: "integer", example: 1)]
    public int $tiene_imagen;
}
