<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\Sale;
use OpenApi\Attributes as OA;

class SaleController
{
    private Sale $saleModel;

    public function __construct()
    {
        $this->saleModel = new Sale();
    }

    #[OA\Post(
        path: "/api/v1/sales/checkout",
        operationId: "checkoutSale",
        summary: "Procesar venta desde el carrito de la tienda / kiosco",
        description: "Registra la venta comanda, partidas de detalle, actualiza inventario, despachos de máquina y genera ticket.",
        tags: ["Ventas"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "cart", type: "array", items: new OA\Items(type: "object")),
                    new OA\Property(property: "metodo_pago", type: "string", example: "Efectivo"),
                    new OA\Property(property: "tipo_pago", type: "integer", example: 0),
                    new OA\Property(property: "tipo_tarjeta", type: "integer", example: 0),
                    new OA\Property(property: "monto_pagado", type: "number", format: "float", example: 100.00),
                    new OA\Property(property: "cambio", type: "number", format: "float", example: 20.00)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Venta procesada exitosamente"),
            new OA\Response(response: 400, description: "Datos inválidos o stock insuficiente"),
            new OA\Response(response: 500, description: "Error interno al procesar la venta")
        ]
    )]
    public function checkout(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['cart'])) {
            Response::error('El carrito de compras no contiene productos válidos.', null, 400);
            return;
        }

        $cart = $input['cart'];
        $metodoPago = $input['metodo_pago'] ?? 'Efectivo';
        $tipoPago = (int)($input['tipo_pago'] ?? 0);
        $tipoTarjeta = (int)($input['tipo_tarjeta'] ?? 0);
        $montoPagado = (float)($input['monto_pagado'] ?? 0.0);
        $cambio = (float)($input['cambio'] ?? 0.0);

        try {
            $result = $this->saleModel->checkout(
                $cart,
                $metodoPago,
                $tipoPago,
                $tipoTarjeta,
                $montoPagado,
                $cambio
            );
            Response::json($result, 200);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), null, 400);
        }
    }

    #[OA\Get(
        path: "/api/v1/sales/{folio}/ticket",
        operationId: "getSaleTicket",
        summary: "Consultar ticket de venta por folio",
        tags: ["Ventas"],
        parameters: [
            new OA\Parameter(name: "folio", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Datos del ticket de venta"),
            new OA\Response(response: 404, description: "Venta no encontrada")
        ]
    )]
    public function getTicket(string $folio): void
    {
        try {
            $ticket = $this->saleModel->getTicketByFolio($folio);
            if (!$ticket) {
                Response::error("No se encontró ninguna venta con el folio especificado.", null, 404);
                return;
            }
            Response::json($ticket, 200);
        } catch (\Throwable $e) {
            Response::error("Error al obtener ticket: " . $e->getMessage(), null, 500);
        }
    }
}
