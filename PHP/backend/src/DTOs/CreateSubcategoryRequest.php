<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateSubcategoryRequest",
    title: "Crear Subcategoría",
    required: ["nombre_subcategoria"]
)]
class CreateSubcategoryRequest
{
    #[OA\Property(property: "nombre_subcategoria", type: "string", example: "Isotónicas")]
    public string $nombre_subcategoria;

    #[OA\Property(property: "imagen_subcategoria", type: "string", nullable: true)]
    public ?string $imagen_subcategoria;
}
