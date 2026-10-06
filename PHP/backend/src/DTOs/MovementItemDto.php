<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MovementItemDto",
    title: "Item de Movimiento / Venta",
    required: ["id_comanda", "folio", "fecha_venta", "total", "metodo_pago"]
)]
class MovementItemDto
{
    #[OA\Property(property: "id_comanda", type: "integer", example: 34)]
    public int $id_comanda;

    #[OA\Property(property: "folio", type: "string", example: "VENTA-20260902-001")]
    public string $folio;

    #[OA\Property(property: "fecha_venta", type: "string", example: "2026-09-02 14:30:00")]
    public string $fecha_venta;

    #[OA\Property(property: "subtotal", type: "number", format: "float", example: 100.00)]
    public float $subtotal;

    #[OA\Property(property: "iva", type: "number", format: "float", example: 16.00)]
    public float $iva;

    #[OA\Property(property: "descuento_global", type: "number", format: "float", example: 0.00)]
    public float $descuento_global;

    #[OA\Property(property: "total", type: "number", format: "float", example: 116.00)]
    public float $total;

    #[OA\Property(property: "metodo_pago", type: "string", example: "Efectivo")]
    public string $metodo_pago;

    #[OA\Property(property: "tipo_pago", type: "integer", nullable: true, example: 1)]
    public ?int $tipo_pago;

    #[OA\Property(property: "tipo_tarjeta", type: "integer", nullable: true, example: 1)]
    public ?int $tipo_tarjeta;

    #[OA\Property(property: "estatus", type: "integer", example: 1)]
    public int $estatus;

    #[OA\Property(property: "notas", type: "string", nullable: true, example: "Venta en terminal quiosco")]
    public ?string $notas;

    #[OA\Property(property: "id_usuario", type: "integer", nullable: true, example: 1)]
    public ?int $id_usuario;

    #[OA\Property(property: "nombre_usuario", type: "string", nullable: true, example: "Administrador")]
    public ?string $nombre_usuario;
}
