<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateCashMovementRequest",
    title: "Registro de Movimiento Manual de Caja",
    required: ["tipo_movimiento", "concepto", "monto"]
)]
class CreateCashMovementRequest
{
    #[OA\Property(property: "tipo_movimiento", type: "string", enum: ["ingreso", "egreso"], example: "egreso")]
    public string $tipo_movimiento;

    #[OA\Property(property: "concepto", type: "string", example: "Compra de cambio de monedas")]
    public string $concepto;

    #[OA\Property(property: "monto", type: "number", format: "float", example: 50.00)]
    public float $monto;

    #[OA\Property(property: "metodo_pago", type: "string", nullable: true, example: "Efectivo")]
    public ?string $metodo_pago;

    #[OA\Property(property: "notas", type: "string", nullable: true)]
    public ?string $notas;
}
