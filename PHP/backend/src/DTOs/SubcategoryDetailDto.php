<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SubcategoryDetailDto",
    title: "Detalle de Subcategoría",
    required: ["id_subcategoria", "id_categoria", "nombre_subcategoria", "nombre_categoria"]
)]
class SubcategoryDetailDto
{
    #[OA\Property(property: "id_subcategoria", type: "integer", example: 3)]
    public int $id_subcategoria;

    #[OA\Property(property: "id_categoria", type: "integer", example: 7)]
    public int $id_categoria;

    #[OA\Property(property: "nombre_subcategoria", type: "string", example: "Refrigerado")]
    public string $nombre_subcategoria;

    #[OA\Property(property: "nombre_categoria", type: "string", example: "Bebida")]
    public string $nombre_categoria;

    #[OA\Property(property: "imagen_subcategoria", type: "string", nullable: true, example: null)]
    public ?string $imagen_subcategoria;
}
