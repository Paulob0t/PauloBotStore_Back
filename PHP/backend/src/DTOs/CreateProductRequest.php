<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateProductRequest",
    title: "Solicitud de Creación de Producto",
    description: "Estructura requerida para registrar un nuevo producto en el catálogo",
    required: [
        "nombre_producto",
        "descripcion",
        "precio",
        "stock",
        "id_categoria",
        "ubicacion",
        "imagen_principal"
    ]
)]
class CreateProductRequest
{
    #[OA\Property(property: "nombre_producto", type: "string", example: "Coca Cola 600ml")]
    public string $nombre_producto;

    #[OA\Property(property: "sku", type: "string", nullable: true, example: "BEB-001")]
    public ?string $sku;

    #[OA\Property(property: "descripcion", type: "string", example: "Bebida refrescante sabor cola de 600 mililitros.")]
    public string $descripcion;

    #[OA\Property(property: "id_categoria", type: "integer", example: 1)]
    public int $id_categoria;

    #[OA\Property(property: "id_subcategoria", type: "integer", nullable: true, example: 4)]
    public ?int $id_subcategoria;

    #[OA\Property(property: "precio", type: "number", format: "float", example: 25.00)]
    public float $precio;

    #[OA\Property(property: "descuento", type: "number", format: "float", nullable: true, example: 2.50)]
    public ?float $descuento;

    #[OA\Property(property: "stock", type: "integer", example: 24)]
    public int $stock;

    #[OA\Property(property: "ubicacion", type: "string", example: "A1", description: "Letra y número de slot (Ej: A1, B2)")]
    public string $ubicacion;

    #[OA\Property(property: "imagen_principal", type: "string", description: "Cadena Base64 o URL de la imagen principal")]
    public string $imagen_principal;

    #[OA\Property(property: "imagen_secundaria_1", type: "string", nullable: true)]
    public ?string $imagen_secundaria_1;

    #[OA\Property(property: "imagen_secundaria_2", type: "string", nullable: true)]
    public ?string $imagen_secundaria_2;

    #[OA\Property(property: "imagen_secundaria_3", type: "string", nullable: true)]
    public ?string $imagen_secundaria_3;

    #[OA\Property(property: "destacado", type: "boolean", example: true)]
    public bool $destacado;

    #[OA\Property(property: "orden_destacado", type: "integer", nullable: true, example: 1)]
    public ?int $orden_destacado;

    #[OA\Property(property: "activo", type: "boolean", example: true)]
    public bool $activo;
}
