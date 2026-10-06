<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CategoryResponse",
    title: "Respuesta de Operación de Categoría",
    required: ["success", "message"]
)]
class CategoryResponse
{
    #[OA\Property(property: "success", type: "boolean", example: true)]
    public bool $success;

    #[OA\Property(property: "message", type: "string", example: "Categoría guardada exitosamente.")]
    public string $message;

    #[OA\Property(property: "id_categoria", type: "integer", nullable: true, example: 5)]
    public ?int $id_categoria;

    #[OA\Property(property: "id_subcategoria", type: "integer", nullable: true, example: 12)]
    public ?int $id_subcategoria;
}
