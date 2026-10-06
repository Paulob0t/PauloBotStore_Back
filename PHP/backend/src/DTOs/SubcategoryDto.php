<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SubcategoryDto",
    title: "Subcategoría",
    required: ["id", "nombre"]
)]
class SubcategoryDto
{
    #[OA\Property(property: "id", type: "integer", example: 4)]
    public int $id;

    #[OA\Property(property: "nombre", type: "string", example: "Refrescos")]
    public string $nombre;
}
