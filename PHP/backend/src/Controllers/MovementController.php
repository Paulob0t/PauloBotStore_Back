<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\MovementItemDto;
use App\DTOs\MovementsSummaryDto;
use App\DTOs\SaleDetailsResponseDto;
use App\Models\Movement;
use OpenApi\Attributes as OA;

class MovementController
{
    private Movement $movementModel;

    public function __construct()
    {
        $this->movementModel = new Movement();
    }

    #[OA\Get(
        path: "/api/v1/movements",
        operationId: "getMovements",
        summary: "Listar ventas y movimientos financieros",
        description: "Retorna el listado cronológico de ventas registradas en el sistema con información de pago y usuario.",
        tags: ["Movimientos"],
        parameters: [
            new OA\Parameter(
                name: "start_date",
                in: "query",
                required: false,
                description: "Fecha inicial (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "end_date",
                in: "query",
                required: false,
                description: "Fecha final (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de ventas y movimientos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/MovementItemDto")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno al consultar movimientos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getAll(): void
    {
        try {
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $movements = $this->movementModel->getAllMovements($startDate, $endDate);
            Response::json($movements, 200);
        } catch (\Throwable $e) {
            Response::error('Error al consultar movimientos: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/movements/summary",
        operationId: "getMovementsSummary",
        summary: "Obtener métricas y KPIs de movimientos",
        description: "Retorna total de ventas, ingresos acumulados, ventas de hoy y ticket promedio.",
        tags: ["Movimientos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen de métricas",
                content: new OA\JsonContent(ref: "#/components/schemas/MovementsSummaryDto")
            )
        ]
    )]
    public function getSummary(): void
    {
        try {
            $summary = $this->movementModel->getSummary();
            Response::json($summary, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener métricas de movimientos: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/movements/{id}",
        operationId: "getMovementById",
        summary: "Obtener detalle completo de un ticket de venta",
        description: "Retorna los productos individuales, cantidades, precios y subtotales pertenecientes a la venta.",
        tags: ["Movimientos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la comanda/venta",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle desglosado de la venta",
                content: new OA\JsonContent(ref: "#/components/schemas/SaleDetailsResponseDto")
            ),
            new OA\Response(
                response: 404,
                description: "Venta no encontrada",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getById(int $id): void
    {
        try {
            $details = $this->movementModel->getSaleDetails($id);
            if (!$details) {
                Response::error('Detalle de venta no encontrado.', null, 404);
                return;
            }
            Response::json($details, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener detalle de venta: ' . $e->getMessage(), null, 500);
        }
    }
}
