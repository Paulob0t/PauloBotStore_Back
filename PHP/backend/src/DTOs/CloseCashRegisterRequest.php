<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CloseCashRegisterRequest",
    title: "Solicitud de Cierre de Caja",
    required: ["monto_final"]
)]
class CloseCashRegisterRequest
{
    #[OA\Property(property: "monto_final", type: "number", format: "float", example: 550.00)]
    public float $monto_final;

    #[OA\Property(property: "notas", type: "string", nullable: true, example: "Cierre sin incidencias")]
    public ?string $notas;
}
