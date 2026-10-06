<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateCashConfigRequest",
    title: "Actualizar Configuración de Caja",
    required: ["corte_automatico_habilitado", "hora_corte_automatico", "monto_inicial_default"]
)]
class UpdateCashConfigRequest
{
    #[OA\Property(property: "corte_automatico_habilitado", type: "boolean", example: true)]
    public bool $corte_automatico_habilitado;

    #[OA\Property(property: "hora_corte_automatico", type: "string", example: "23:59:00")]
    public string $hora_corte_automatico;

    #[OA\Property(property: "monto_inicial_default", type: "number", format: "float", example: 100.00)]
    public float $monto_inicial_default;
}
