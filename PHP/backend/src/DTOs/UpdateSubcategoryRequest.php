<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateSubcategoryRequest",
    title: "Actualizar Subcategoría",
    required: ["nombre_subcategoria"]
)]
class UpdateSubcategoryRequest
{
    #[OA\Property(property: "nombre_subcategoria", type: "string", example: "Refrescos y Sodas")]
    public string $nombre_subcategoria;

    #[OA\Property(property: "id_categoria", type: "integer", nullable: true, example: 3)]
    public ?int $id_categoria;
}
