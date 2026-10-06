<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterTotalsDto",
    title: "Totales de Caja en Vivo",
    required: ["monto_inicial", "total_ingresos", "total_egresos", "num_ingresos", "num_egresos", "monto_esperado"]
)]
class CashRegisterTotalsDto
{
    #[OA\Property(property: "monto_inicial", type: "number", format: "float", example: 100.00)]
    public float $monto_inicial;

    #[OA\Property(property: "total_ingresos", type: "number", format: "float", example: 450.00)]
    public float $total_ingresos;

    #[OA\Property(property: "total_egresos", type: "number", format: "float", example: 50.00)]
    public float $total_egresos;

    #[OA\Property(property: "num_ingresos", type: "integer", example: 5)]
    public int $num_ingresos;

    #[OA\Property(property: "num_egresos", type: "integer", example: 1)]
    public int $num_egresos;

    #[OA\Property(property: "monto_esperado", type: "number", format: "float", example: 500.00)]
    public float $monto_esperado;
}
