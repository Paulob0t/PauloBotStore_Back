<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ProductResponse",
    title: "Respuesta de Operación de Producto",
    required: ["success", "message"]
)]
class ProductResponse
{
    #[OA\Property(property: "success", type: "boolean", example: true)]
    public bool $success;

    #[OA\Property(property: "message", type: "string", example: "Producto creado exitosamente.")]
    public string $message;

    #[OA\Property(property: "id_producto", type: "integer", nullable: true, example: 15)]
    public ?int $id_producto;
}
