<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class Sale
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Procesar una venta completa desde el carrito web/kiosco
     *
     * @param array $cart Lista de items con id_producto y quantity
     * @param string $metodoPago 'Efectivo', 'Tarjeta' o similar
     * @param int $tipoPago 0=Efectivo, 1=Tarjeta
     * @param int $tipoTarjeta 0=No aplica, 1=Débito, 2=Crédito
     * @param float $montoPagado Dinero entregado por el cliente
     * @param float $cambio Cambio a devolver
     * @return array Resultado de la venta con folio, total y datos de ticket
     * @throws Exception
     */
    public function checkout(
        array $cart,
        string $metodoPago = 'Efectivo',
        int $tipoPago = 0,
        int $tipoTarjeta = 0,
        float $montoPagado = 0.0,
        float $cambio = 0.0
    ): array {
        if (!$this->db || $this->db->connect_error) {
            throw new Exception("Error de conexión a la base de datos.");
        }

        if (empty($cart)) {
            throw new Exception("El carrito está vacío.");
        }

        $this->db->begin_transaction();

        try {
            $subtotal = 0.0;
            $totalDescuento = 0.0;
            $total = 0.0;
            $itemsValidados = [];

            // 1. Validar productos, calcular totales y verificar stock
            foreach ($cart as $item) {
                $idProducto = (int)($item['id_producto'] ?? 0);
                $quantity = (int)($item['quantity'] ?? ($item['cantidad'] ?? 1));

                if ($idProducto <= 0 || $quantity <= 0) {
                    throw new Exception("Parámetros de producto inválidos en el carrito.");
                }

                $stmt = $this->db->prepare("SELECT id_producto, nombre_producto, precio, descuento, stock, sku, ubicacion, activo 
                                            FROM productos WHERE id_producto = ? FOR UPDATE");
                $stmt->bind_param("i", $idProducto);
                $stmt->execute();
                $prod = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$prod || (int)$prod['activo'] !== 1) {
                    throw new Exception("El producto ID {$idProducto} no está disponible o no existe.");
                }

                $stockActual = (int)($prod['stock'] ?? 0);
                if ($stockActual < $quantity) {
                    throw new Exception("Stock insuficiente para '{$prod['nombre_producto']}'. Disponibles: {$stockActual}, solicitados: {$quantity}.");
                }

                $precioOriginal = (float)$prod['precio'];
                $descuentoVal = (float)($prod['descuento'] ?? 0);
                $descuentoPesos = max(0.0, min($precioOriginal, $descuentoVal));
                $precioUnitarioFinal = max(0.0, $precioOriginal - $descuentoPesos);

                $itemSubtotal = $precioUnitarioFinal * $quantity;
                $subtotal += $itemSubtotal;
                $totalDescuento += ($descuentoPesos * $quantity);
                $total += $itemSubtotal;

                $itemsValidados[] = [
                    'id_producto' => $idProducto,
                    'nombre_producto' => $prod['nombre_producto'],
                    'sku' => $prod['sku'] ?: ('SKU-' . $idProducto),
                    'ubicacion' => $prod['ubicacion'] ?: 'A001',
                    'cantidad' => $quantity,
                    'precio_original' => $precioOriginal,
                    'descuento_pesos' => $descuentoPesos,
                    'precio_final' => $precioUnitarioFinal,
                    'subtotal' => $itemSubtotal,
                    'stock_restante' => $stockActual - $quantity
                ];
            }

            // 2. Generar folio único
            $folio = 'VENTA-' . date('Ymd-His') . '-' . rand(1000, 9999);
            $idPago = ($tipoPago === 1) ? 'CARD-' . uniqid() : 'CASH-' . uniqid();
            $idUsuario = 1;
            $idCliente = 1;
            $estatus = 1; // 1 = Pagado/Completado
            $notas = "Venta realizada desde el Kiosco PauloBot Store";

            $subtotalSinIva = $total / 1.16;
            $ivaIncluido = $total - $subtotalSinIva;

            $subtotalRounded = round($subtotal, 2);
            $ivaRounded = round($ivaIncluido, 2);
            $descuentoRounded = round($totalDescuento, 2);
            $totalRounded = round($total, 2);

            // 3. Insertar comanda / venta principal
            $sqlVenta = "INSERT INTO ventas_comanda (
                folio, id_usuario, id_cliente, fecha_venta, subtotal, iva, descuento_global, 
                total, metodo_pago, estatus, notas, fecha_creacion, fecha_actualizacion, 
                id_pago, tipo_pago, tipo_tarjeta
            ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?)";

            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->bind_param(
                "siiddddsissii",
                $folio,
                $idUsuario,
                $idCliente,
                $subtotalRounded,
                $ivaRounded,
                $descuentoRounded,
                $totalRounded,
                $metodoPago,
                $estatus,
                $notas,
                $idPago,
                $tipoPago,
                $tipoTarjeta
            );

            if (!$stmtVenta->execute()) {
                throw new Exception("Error al registrar venta principal: " . $stmtVenta->error);
            }
            $idComanda = $this->db->insert_id;
            $stmtVenta->close();

            // Log de sincronización comanda
            $this->logSync('ventas_comanda', 'INSERT', $idComanda, [
                'id_comanda' => $idComanda,
                'folio' => $folio,
                'total' => $totalRounded,
                'metodo_pago' => $metodoPago,
                'fecha_venta' => date('Y-m-d H:i:s')
            ]);

            // 4. Insertar detalles, despachos para arduino y descontar stock
            $sqlDetalle = "INSERT INTO ventas_detalle (
                id_comandC, id_producto, cantidad, precio_unitario, descuento_unitario, 
                subtotal, iva_unitario, total, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            $sqlDespacho = "INSERT INTO despachos_arduino (
                id_comanda, id_producto, sku, cantidad, ubicacion, id_pago, 
                estatus_despacho, fecha_registro, notas
            ) VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), ?)";
            $stmtDespacho = $this->db->prepare($sqlDespacho);

            $sqlStock = "UPDATE productos SET stock = ? WHERE id_producto = ?";
            $stmtStock = $this->db->prepare($sqlStock);

            $despachos = [];
            $productosTicket = [];

            foreach ($itemsValidados as $item) {
                $pUnitario = round($item['precio_original'], 2);
                $dUnitario = round($item['descuento_pesos'], 2);
                $subItem = round($item['subtotal'], 2);
                $ivaUnitario = round($item['precio_final'] * 0.16, 2);
                $totItem = round($subItem, 2);
                $notaDetalle = "Item: {$item['nombre_producto']} x {$item['cantidad']}";

                $stmtDetalle->bind_param(
                    "iiiddddds",
                    $idComanda,
                    $item['id_producto'],
                    $item['cantidad'],
                    $pUnitario,
                    $dUnitario,
                    $subItem,
                    $ivaUnitario,
                    $totItem,
                    $notaDetalle
                );
                $stmtDetalle->execute();
                $idDetalle = $this->db->insert_id;

                $this->logSync('ventas_detalle', 'INSERT', $idDetalle, [
                    'id_detalle' => $idDetalle,
                    'id_comanda' => $idComanda,
                    'id_producto' => $item['id_producto'],
                    'cantidad' => $item['cantidad'],
                    'total' => $totItem
                ]);

                // Despacho Arduino
                $stmtDespacho->bind_param(
                    "isissss",
                    $idComanda,
                    $item['id_producto'],
                    $item['sku'],
                    $item['cantidad'],
                    $item['ubicacion'],
                    $idPago,
                    $notaDetalle
                );
                $stmtDespacho->execute();
                $idDespacho = $this->db->insert_id;

                $this->logSync('despachos_arduino', 'INSERT', $idDespacho, [
                    'id_despacho' => $idDespacho,
                    'id_comanda' => $idComanda,
                    'ubicacion' => $item['ubicacion'],
                    'cantidad' => $item['cantidad']
                ]);

                $despachos[] = [
                    'id_despacho' => $idDespacho,
                    'producto' => $item['nombre_producto'],
                    'ubicacion' => $item['ubicacion'],
                    'cantidad' => $item['cantidad'],
                    'sku' => $item['sku'],
                    'estatus' => 'Listo para Despacho'
                ];

                // Descontar inventario
                $stmtStock->bind_param("ii", $item['stock_restante'], $item['id_producto']);
                $stmtStock->execute();

                $productosTicket[] = [
                    'id_producto' => $item['id_producto'],
                    'nombre' => $item['nombre_producto'],
                    'cantidad' => $item['cantidad'],
                    'ubicacion' => $item['ubicacion'],
                    'precio_original' => $item['precio_original'],
                    'descuento' => $item['descuento_pesos'],
                    'precio' => $item['precio_final'],
                    'subtotal' => $item['subtotal']
                ];
            }

            $stmtDetalle->close();
            $stmtDespacho->close();
            $stmtStock->close();

            // 5. Registrar en corte de caja si existe caja abierta
            try {
                $cashRegisterModel = new CashRegister();
                $activeCut = $cashRegisterModel->getActiveCut();
                if ($activeCut && isset($activeCut['id_corte'])) {
                    $idCorte = (int)$activeCut['id_corte'];
                    $metodoCaja = ($tipoPago === 1) ? 'tarjeta' : 'efectivo';
                    $cashRegisterModel->addMovement([
                        'id_corte' => $idCorte,
                        'tipo' => 'ingreso',
                        'concepto' => "Venta Folio {$folio}",
                        'monto' => $totalRounded,
                        'metodo_pago' => $metodoCaja,
                        'id_venta' => $idComanda,
                        'id_usuario' => $idUsuario,
                        'notas' => "Venta automática Kiosco Store - " . count($cart) . " productos"
                    ]);
                }
            } catch (\Throwable $e) {
                error_log("⚠️ Advertencia al registrar venta en corte de caja: " . $e->getMessage());
            }

            $this->db->commit();

            // 6. Obtener datos de la empresa para ticket
            $companyConfig = new CompanyConfig();
            $empresa = $companyConfig->get();

            return [
                'success' => true,
                'mensaje' => 'Venta procesada exitosamente',
                'id_comanda' => $idComanda,
                'folio' => $folio,
                'total' => $totalRounded,
                'subtotal' => $subtotalRounded,
                'descuento' => $descuentoRounded,
                'iva' => $ivaRounded,
                'metodo_pago' => $metodoPago,
                'monto_pagado' => $montoPagado > 0 ? $montoPagado : $totalRounded,
                'cambio' => $cambio > 0 ? $cambio : 0.0,
                'despachos' => $despachos,
                'ticket_data' => [
                    'folio' => $folio,
                    'fecha' => date('d/m/Y H:i:s'),
                    'cajero' => 'Kiosco PauloBot Store 24/7',
                    'productos' => $productosTicket,
                    'subtotal' => $subtotalRounded,
                    'descuento' => $descuentoRounded,
                    'iva' => $ivaRounded,
                    'total' => $totalRounded,
                    'metodo_pago' => $metodoPago,
                    'monto_pagado' => $montoPagado > 0 ? $montoPagado : $totalRounded,
                    'cambio' => $cambio > 0 ? $cambio : 0.0,
                    'empresa' => $empresa
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtener los datos de un ticket por su folio
     */
    public function getTicketByFolio(string $folio): ?array
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id_comanda, folio, fecha_venta, subtotal, iva, descuento_global, total, metodo_pago, id_pago, tipo_pago 
                                    FROM ventas_comanda WHERE folio = ? LIMIT 1");
        $stmt->bind_param("s", $folio);
        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$venta) {
            return null;
        }

        $idComanda = (int)$venta['id_comanda'];

        $stmtDetalles = $this->db->prepare("
            SELECT vd.id_producto, vd.cantidad, vd.precio_unitario, vd.descuento_unitario, vd.subtotal, 
                   p.nombre_producto, p.ubicacion
            FROM ventas_detalle vd
            LEFT JOIN productos p ON vd.id_producto = p.id_producto
            WHERE vd.id_comandC = ?
        ");
        $stmtDetalles->bind_param("i", $idComanda);
        $stmtDetalles->execute();
        $resDetalles = $stmtDetalles->get_result();

        $productos = [];
        while ($row = $resDetalles->fetch_assoc()) {
            $productos[] = [
                'id_producto' => (int)$row['id_producto'],
                'nombre' => $row['nombre_producto'],
                'cantidad' => (int)$row['cantidad'],
                'ubicacion' => $row['ubicacion'],
                'precio_original' => (float)$row['precio_unitario'],
                'descuento' => (float)$row['descuento_unitario'],
                'precio' => (float)($row['precio_unitario'] - $row['descuento_unitario']),
                'subtotal' => (float)$row['subtotal']
            ];
        }
        $stmtDetalles->close();

        $companyConfig = new CompanyConfig();
        $empresa = $companyConfig->get();

        return [
            'folio' => $venta['folio'],
            'fecha' => date('d/m/Y H:i:s', strtotime($venta['fecha_venta'])),
            'cajero' => 'Kiosco PauloBot Store 24/7',
            'productos' => $productos,
            'subtotal' => (float)$venta['subtotal'],
            'descuento' => (float)$venta['descuento_global'],
            'iva' => (float)$venta['iva'],
            'total' => (float)$venta['total'],
            'metodo_pago' => $venta['metodo_pago'],
            'empresa' => $empresa
        ];
    }

    private function logSync(string $tabla, string $accion, int $idRegistro, array $datos): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO sincronizacion_log (tabla, accion, id_registro, datos, origen, sincronizado) VALUES (?, ?, ?, ?, 'LOCAL', 0)");
            $datosJson = json_encode($datos);
            $stmt->bind_param("ssis", $tabla, $accion, $idRegistro, $datosJson);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            error_log("⚠️ Error en logSync: " . $e->getMessage());
        }
    }
}
