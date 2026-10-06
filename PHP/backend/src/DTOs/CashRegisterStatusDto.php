<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterStatusDto",
    title: "Estado Actual de la Caja",
    required: ["caja_activa", "config"]
)]
class CashRegisterStatusDto
{
    #[OA\Property(property: "caja_activa", type: "boolean", example: true)]
    public bool $caja_activa;

    #[OA\Property(property: "config", ref: "#/components/schemas/CashRegisterConfigDto")]
    public CashRegisterConfigDto $config;

    #[OA\Property(property: "corte_actual", ref: "#/components/schemas/CashRegisterCutDto", nullable: true)]
    public ?CashRegisterCutDto $corte_actual;

    #[OA\Property(property: "totales", ref: "#/components/schemas/CashRegisterTotalsDto", nullable: true)]
    public ?CashRegisterTotalsDto $totales;

    #[OA\Property(
        property: "movimientos",
        type: "array",
        items: new OA\Items(ref: "#/components/schemas/CashRegisterMovementDto")
    )]
    public array $movimientos;
}
