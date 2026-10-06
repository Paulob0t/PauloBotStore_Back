<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CategoryDto",
    title: "Categoría con Subcategorías",
    required: ["id", "nombre", "subcategorias"]
)]
class CategoryDto
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "nombre", type: "string", example: "Bebidas")]
    public string $nombre;

    #[OA\Property(property: "tiene_imagen", type: "integer", example: 1)]
    public int $tiene_imagen;

    #[OA\Property(
        property: "subcategorias",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/SubcategoryDto")
    )]
    public array $subcategorias;
}
