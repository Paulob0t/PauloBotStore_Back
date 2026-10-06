<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CashRegisterConfigDto",
    title: "Configuración de Caja",
    required: ["caja_activa", "corte_automatico_habilitado", "hora_corte_automatico", "monto_inicial_default"]
)]
class CashRegisterConfigDto
{
    #[OA\Property(property: "caja_activa", type: "boolean", example: true)]
    public bool $caja_activa;

    #[OA\Property(property: "id_corte_actual", type: "integer", nullable: true, example: 5)]
    public ?int $id_corte_actual;

    #[OA\Property(property: "fecha_ultimo_corte", type: "string", nullable: true, example: "2026-09-02 23:59:00")]
    public ?string $fecha_ultimo_corte;

    #[OA\Property(property: "corte_automatico_habilitado", type: "boolean", example: true)]
    public bool $corte_automatico_habilitado;

    #[OA\Property(property: "hora_corte_automatico", type: "string", example: "23:59:00")]
    public string $hora_corte_automatico;

    #[OA\Property(property: "monto_inicial_default", type: "number", format: "float", example: 100.00)]
    public float $monto_inicial_default;
}
