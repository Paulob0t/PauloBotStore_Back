<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateCategoryRequest",
    title: "Crear Categoría",
    required: ["nombre_categoria"]
)]
class CreateCategoryRequest
{
    #[OA\Property(property: "nombre_categoria", type: "string", example: "Bebidas")]
    public string $nombre_categoria;

    #[OA\Property(property: "imagen_categoria", type: "string", nullable: true, example: null)]
    public ?string $imagen_categoria;

    #[OA\Property(
        property: "subcategorias",
        type: "array",
        items: new OA\Items(type: "string"),
        nullable: true,
        example: ["Refrescos", "Jugos", "Agua"]
    )]
    public ?array $subcategorias;
}
