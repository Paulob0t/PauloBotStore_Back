<?php

namespace App\Models;

use App\Core\Database;
use mysqli;

class Movement
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todas las ventas / movimientos con datos de usuario.
     */
    public function getAllMovements(?string $startDate = null, ?string $endDate = null): array
    {
        $movements = [];
        if (!$this->db || $this->db->connect_error) {
            return $movements;
        }

        $sql = "SELECT 
                    vc.id_comanda,
                    vc.folio,
                    vc.fecha_venta,
                    vc.subtotal,
                    vc.iva,
                    vc.descuento_global,
                    vc.total,
                    vc.metodo_pago,
                    vc.tipo_pago,
                    vc.tipo_tarjeta,
                    vc.estatus,
                    vc.notas,
                    vc.id_usuario,
                    u.nombre AS nombre_usuario
                FROM ventas_comanda vc
                LEFT JOIN usuarios u ON vc.id_usuario = u.id";

        $conditions = [];
        $params = [];
        $types = "";

        if (!empty($startDate) && !empty($endDate)) {
            $conditions[] = "DATE(vc.fecha_venta) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY vc.fecha_venta DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $movements[] = $this->mapMovementRow($row);
                }
                $stmt->close();
            }
        } else {
            if ($res = $this->db->query($sql)) {
                while ($row = $res->fetch_assoc()) {
                    $movements[] = $this->mapMovementRow($row);
                }
                $res->free();
            }
        }

        return $movements;
    }

    /**
     * Obtener resumen general y KPIs de ventas.
     */
    public function getSummary(): array
    {
        $default = [
            'totalVentas' => 0,
            'totalIngresos' => 0.0,
            'ventasHoy' => 0,
            'promedioVenta' => 0.0
        ];

        if (!$this->db || $this->db->connect_error) {
            return $default;
        }

        $sql = "SELECT 
                    COUNT(*) AS total_ventas,
                    COALESCE(SUM(total), 0) AS total_ingresos,
                    COALESCE(SUM(CASE WHEN DATE(fecha_venta) = CURDATE() THEN 1 ELSE 0 END), 0) AS ventas_hoy,
                    COALESCE(AVG(total), 0) AS promedio_venta
                FROM ventas_comanda";

        if ($res = $this->db->query($sql)) {
            $row = $res->fetch_assoc();
            $res->free();
            return [
                'totalVentas' => (int)($row['total_ventas'] ?? 0),
                'totalIngresos' => (float)($row['total_ingresos'] ?? 0),
                'ventasHoy' => (int)($row['ventas_hoy'] ?? 0),
                'promedioVenta' => (float)($row['promedio_venta'] ?? 0)
            ];
        }

        return $default;
    }

    /**
     * Obtener los productos desglosados de un ticket de venta específico.
     */
    public function getSaleDetails(int $idComanda): ?array
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        // Obtener cabecera
        $headerStmt = $this->db->prepare("SELECT id_comanda, folio, fecha_venta, subtotal, total, metodo_pago, notas 
                                         FROM ventas_comanda WHERE id_comanda = ? LIMIT 1");
        if (!$headerStmt) return null;

        $headerStmt->bind_param("i", $idComanda);
        $headerStmt->execute();
        $headerRes = $headerStmt->get_result();
        $header = $headerRes->fetch_assoc();
        $headerStmt->close();

        if (!$header) return null;

        // Obtener items
        $itemsStmt = $this->db->prepare("SELECT 
                                            vd.id_detalle,
                                            vd.id_producto,
                                            p.nombre_producto,
                                            p.sku,
                                            p.descripcion,
                                            vd.cantidad,
                                            vd.precio_unitario,
                                            vd.descuento_unitario,
                                            vd.subtotal,
                                            vd.iva_unitario,
                                            vd.total,
                                            vd.notas as notas_producto
                                        FROM ventas_detalle vd
                                        INNER JOIN productos p ON vd.id_producto = p.id_producto
                                        WHERE vd.id_comandC = ?
                                        ORDER BY vd.id_detalle ASC");
        if (!$itemsStmt) return null;

        $itemsStmt->bind_param("i", $idComanda);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();
        $items = [];

        while ($item = $itemsRes->fetch_assoc()) {
            $items[] = [
                'id_detalle' => (int)$item['id_detalle'],
                'id_producto' => (int)$item['id_producto'],
                'nombre_producto' => (string)$item['nombre_producto'],
                'sku' => $item['sku'] ?: null,
                'descripcion' => $item['descripcion'] ?: null,
                'cantidad' => (int)$item['cantidad'],
                'precio_unitario' => (float)$item['precio_unitario'],
                'descuento_unitario' => (float)$item['descuento_unitario'],
                'subtotal' => (float)$item['subtotal'],
                'iva_unitario' => (float)$item['iva_unitario'],
                'total' => (float)$item['total'],
                'notas_producto' => $item['notas_producto'] ?: null
            ];
        }
        $itemsStmt->close();

        return [
            'id_comanda' => (int)$header['id_comanda'],
            'folio' => (string)$header['folio'],
            'fecha_venta' => (string)$header['fecha_venta'],
            'subtotal' => (float)$header['subtotal'],
            'total' => (float)$header['total'],
            'metodo_pago' => (string)$header['metodo_pago'],
            'notas' => $header['notas'] ?: null,
            'productos' => $items
        ];
    }

    private function mapMovementRow(array $row): array
    {
        return [
            'id_comanda' => (int)$row['id_comanda'],
            'folio' => (string)$row['folio'],
            'fecha_venta' => (string)$row['fecha_venta'],
            'subtotal' => (float)$row['subtotal'],
            'iva' => (float)$row['iva'],
            'descuento_global' => (float)$row['descuento_global'],
            'total' => (float)$row['total'],
            'metodo_pago' => (string)($row['metodo_pago'] ?: 'Efectivo'),
            'tipo_pago' => $row['tipo_pago'] !== null ? (int)$row['tipo_pago'] : null,
            'tipo_tarjeta' => $row['tipo_tarjeta'] !== null ? (int)$row['tipo_tarjeta'] : null,
            'estatus' => (int)$row['estatus'],
            'notas' => $row['notas'] ?: null,
            'id_usuario' => $row['id_usuario'] !== null ? (int)$row['id_usuario'] : null,
            'nombre_usuario' => $row['nombre_usuario'] ?: 'Administrador'
        ];
    }
}
