<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ChartDto",
    title: "Datos de Gráfica de Ventas",
    required: ["labels", "ventas", "montos"]
)]
class ChartDto
{
    #[OA\Property(property: "labels", type: "array", items: new OA\Items(type: "string"), example: ["26/08", "27/08", "28/08", "29/08", "30/08", "31/08", "01/09"])]
    public array $labels;

    #[OA\Property(property: "ventas", type: "array", items: new OA\Items(type: "integer"), example: [5, 12, 8, 15, 9, 20, 14])]
    public array $ventas;

    #[OA\Property(property: "montos", type: "array", items: new OA\Items(type: "number", format: "float"), example: [150.0, 420.5, 280.0, 560.0, 310.0, 720.0, 490.0])]
    public array $montos;
}
