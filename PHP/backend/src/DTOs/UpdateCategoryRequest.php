<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateCategoryRequest",
    title: "Actualizar Categoría",
    required: ["nombre_categoria"]
)]
class UpdateCategoryRequest
{
    #[OA\Property(property: "nombre_categoria", type: "string", example: "Bebidas Energéticas")]
    public string $nombre_categoria;

    #[OA\Property(property: "imagen_categoria", type: "string", nullable: true)]
    public ?string $imagen_categoria;
}
