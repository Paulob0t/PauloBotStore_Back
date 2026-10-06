<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterActionResponse",
    title: "Respuesta de Acción en Caja",
    required: ["success", "message"]
)]
class CashRegisterActionResponse
{
    #[OA\Property(property: "success", type: "boolean", example: true)]
    public bool $success;

    #[OA\Property(property: "message", type: "string", example: "Caja iniciada exitosamente.")]
    public string $message;

    #[OA\Property(property: "id_corte", type: "integer", nullable: true, example: 1)]
    public ?int $id_corte;

    #[OA\Property(property: "monto_esperado", type: "number", format: "float", nullable: true, example: 500.00)]
    public ?float $monto_esperado;

    #[OA\Property(property: "monto_declarado", type: "number", format: "float", nullable: true, example: 500.00)]
    public ?float $monto_declarado;

    #[OA\Property(property: "diferencia", type: "number", format: "float", nullable: true, example: 0.00)]
    public ?float $diferencia;
}
