<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterMovementDto",
    title: "Movimiento Interno de Caja",
    required: ["id", "id_corte", "tipo_movimiento", "concepto", "monto", "fecha_hora"]
)]
class CashRegisterMovementDto
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "id_corte", type: "integer", example: 1)]
    public int $id_corte;

    #[OA\Property(property: "tipo_movimiento", type: "string", enum: ["ingreso", "egreso", "apertura", "cierre"], example: "ingreso")]
    public string $tipo_movimiento;

    #[OA\Property(property: "concepto", type: "string", example: "Venta Folio #VENTA-001")]
    public string $concepto;

    #[OA\Property(property: "monto", type: "number", format: "float", example: 50.00)]
    public float $monto;

    #[OA\Property(property: "metodo_pago", type: "string", nullable: true, example: "Efectivo")]
    public ?string $metodo_pago;

    #[OA\Property(property: "referencia", type: "string", nullable: true)]
    public ?string $referencia;

    #[OA\Property(property: "id_venta", type: "integer", nullable: true)]
    public ?int $id_venta;

    #[OA\Property(property: "nombre_usuario", type: "string", nullable: true, example: "Admin")]
    public ?string $nombre_usuario;

    #[OA\Property(property: "fecha_hora", type: "string", example: "2026-09-02 15:45:00")]
    public string $fecha_hora;

    #[OA\Property(property: "notas", type: "string", nullable: true)]
    public ?string $notas;
}
