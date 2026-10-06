<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterCutDto",
    title: "Registro de Corte de Caja",
    required: ["id", "fecha", "hora", "tipo_movimiento", "monto_inicial"]
)]
class CashRegisterCutDto
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "fecha", type: "string", example: "2026-09-02")]
    public string $fecha;

    #[OA\Property(property: "hora", type: "string", example: "14:30:00")]
    public string $hora;

    #[OA\Property(property: "tipo_movimiento", type: "string", enum: ["inicio", "fin"], example: "fin")]
    public string $tipo_movimiento;

    #[OA\Property(property: "monto_inicial", type: "number", format: "float", example: 100.00)]
    public float $monto_inicial;

    #[OA\Property(property: "monto_final", type: "number", format: "float", nullable: true, example: 550.00)]
    public ?float $monto_final;

    #[OA\Property(property: "total_ingresos", type: "number", format: "float", nullable: true, example: 450.00)]
    public ?float $total_ingresos;

    #[OA\Property(property: "total_egresos", type: "number", format: "float", nullable: true, example: 0.00)]
    public ?float $total_egresos;

    #[OA\Property(property: "diferencia", type: "number", format: "float", nullable: true, example: 0.00)]
    public ?float $diferencia;

    #[OA\Property(property: "id_usuario", type: "integer", nullable: true, example: 1)]
    public ?int $id_usuario;

    #[OA\Property(property: "nombre_usuario", type: "string", nullable: true, example: "Administrador")]
    public ?string $nombre_usuario;

    #[OA\Property(property: "notas", type: "string", nullable: true, example: "Corte regular del turno")]
    public ?string $notas;
}
