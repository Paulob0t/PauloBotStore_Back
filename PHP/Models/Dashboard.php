<?php

namespace App\Models;

use App\Core\Database;
use mysqli;

class Dashboard
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener métricas de ventas de hoy y del mes actual.
     */
    public function getSalesMetrics(): array
    {
        $hoy = date('Y-m-d');
        $mesActual = date('Y-m');

        $ventasHoyCnt = 0;
        $ventasHoyMonto = 0.0;
        $ventasMesCnt = 0;
        $ventasMesMonto = 0.0;
        $promedioVenta = 0.0;

        if ($this->db && !$this->db->connect_error) {
            // Ventas de hoy
            $sqlHoy = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS monto
                       FROM ventas_comanda
                       WHERE DATE(fecha_venta) = ? AND estatus = 1";
            if ($stmt = $this->db->prepare($sqlHoy)) {
                $stmt->bind_param('s', $hoy);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $ventasHoyCnt = (int)($row['cnt'] ?? 0);
                $ventasHoyMonto = (float)($row['monto'] ?? 0);
                $stmt->close();
            }

            // Ventas del mes
            $sqlMes = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS monto
                       FROM ventas_comanda
                       WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ? AND estatus = 1";
            if ($stmt = $this->db->prepare($sqlMes)) {
                $stmt->bind_param('s', $mesActual);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $ventasMesCnt = (int)($row['cnt'] ?? 0);
                $ventasMesMonto = (float)($row['monto'] ?? 0);
                $stmt->close();
            }

            if ($ventasMesCnt > 0) {
                $promedioVenta = $ventasMesMonto / $ventasMesCnt;
            }
        }

        return [
            'ventasHoyCnt' => $ventasHoyCnt,
            'ventasHoyMonto' => $ventasHoyMonto,
            'ventasMesCnt' => $ventasMesCnt,
            'ventasMesMonto' => $ventasMesMonto,
            'promedioVenta' => $promedioVenta,
            'fechaLabel' => date('d/m/Y')
        ];
    }

    /**
     * Obtener métricas generales del inventario de productos.
     */
    public function getInventoryMetrics(): array
    {
        $totalProductos = 0;
        $stockTotal = 0;
        $stockBajo = 0;
        $productosInactivos = 0;

        if ($this->db && !$this->db->connect_error) {
            $sqlProd = "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(stock), 0) AS stock_total,
                SUM(CASE WHEN stock <= 5 AND activo = 1 THEN 1 ELSE 0 END) AS stock_bajo,
                SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
                FROM productos";
            if ($res = $this->db->query($sqlProd)) {
                $p = $res->fetch_assoc();
                $totalProductos = (int)($p['total'] ?? 0);
                $stockTotal = (int)($p['stock_total'] ?? 0);
                $stockBajo = (int)($p['stock_bajo'] ?? 0);
                $productosInactivos = (int)($p['inactivos'] ?? 0);
            }
        }

        return [
            'totalProductos' => $totalProductos,
            'stockTotal' => $stockTotal,
            'stockBajo' => $stockBajo,
            'productosInactivos' => $productosInactivos
        ];
    }

    /**
     * Obtener ventas de los últimos 7 días para la gráfica de tendencia.
     */
    public function getSalesLast7DaysChart(): array
    {
        $chartLabels = [];
        $chartVentas = [];
        $chartMontos = [];

        for ($i = 6; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-$i days"));
            $chartLabels[] = date('d/m', strtotime($dia));
            $chartVentas[$dia] = 0;
            $chartMontos[$dia] = 0.0;
        }

        if ($this->db && !$this->db->connect_error) {
            $sql7d = "SELECT DATE(fecha_venta) AS dia, COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS monto
                      FROM ventas_comanda
                      WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estatus = 1
                      GROUP BY DATE(fecha_venta)";
            if ($res = $this->db->query($sql7d)) {
                while ($r = $res->fetch_assoc()) {
                    $d = $r['dia'];
                    if (isset($chartVentas[$d])) {
                        $chartVentas[$d] = (int)$r['ventas'];
                        $chartMontos[$d] = (float)$r['monto'];
                    }
                }
            }
        }

        return [
            'labels' => $chartLabels,
            'ventas' => array_values($chartVentas),
            'montos' => array_values($chartMontos)
        ];
    }

    /**
     * Obtener el top de productos más vendidos en los últimos 30 días.
     */
    public function getTopProducts(int $limit = 5): array
    {
        $topProductos = [];
        if ($this->db && !$this->db->connect_error) {
            $sqlTop = "SELECT p.nombre_producto, SUM(vd.cantidad) AS unidades, COALESCE(SUM(vd.total), 0) AS ingresos
                       FROM ventas_detalle vd
                       INNER JOIN productos p ON p.id_producto = vd.id_producto
                       INNER JOIN ventas_comanda vc ON vc.id_comanda = vd.id_comandC
                       WHERE vc.fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND vc.estatus = 1
                       GROUP BY p.id_producto, p.nombre_producto
                       ORDER BY unidades DESC
                       LIMIT ?";
            if ($stmt = $this->db->prepare($sqlTop)) {
                $stmt->bind_param('i', $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $topProductos[] = $r;
                }
                $stmt->close();
            }
        }
        return $topProductos;
    }

    /**
     * Obtener el listado de las últimas ventas registradas.
     */
    public function getRecentSales(int $limit = 6): array
    {
        $ultimasVentas = [];
        if ($this->db && !$this->db->connect_error) {
            $sqlUlt = "SELECT folio, fecha_venta, total, metodo_pago, tipo_pago
                       FROM ventas_comanda
                       WHERE estatus = 1
                       ORDER BY fecha_venta DESC
                       LIMIT ?";
            if ($stmt = $this->db->prepare($sqlUlt)) {
                $stmt->bind_param('i', $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $ultimasVentas[] = $r;
                }
                $stmt->close();
            }
        }
        return $ultimasVentas;
    }
}
