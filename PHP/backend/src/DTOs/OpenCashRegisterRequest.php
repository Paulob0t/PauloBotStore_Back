<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "OpenCashRegisterRequest",
    title: "Solicitud de Apertura de Caja",
    required: ["monto_inicial"]
)]
class OpenCashRegisterRequest
{
    #[OA\Property(property: "monto_inicial", type: "number", format: "float", example: 100.00)]
    public float $monto_inicial;

    #[OA\Property(property: "notas", type: "string", nullable: true, example: "Apertura turno matutino")]
    public ?string $notas;
}
