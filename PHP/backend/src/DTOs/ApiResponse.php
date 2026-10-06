<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ApiResponse",
    title: "Respuesta Genérica de la API",
    description: "Estructura estándar de respuesta para operaciones generales"
)]
class ApiResponse
{
    #[OA\Property(property: "success", type: "boolean", example: true)]
    public bool $success;

    #[OA\Property(property: "message", type: "string", example: "Operación realizada correctamente")]
    public string $message;

    #[OA\Property(property: "data", type: "object", nullable: true)]
    public mixed $data;
}
