<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class CashRegister
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getConfig(): array
    {
        $default = [
            'caja_activa' => false,
            'id_corte_actual' => null,
            'fecha_ultimo_corte' => null,
            'corte_automatico_habilitado' => true,
            'hora_corte_automatico' => '23:59:00',
            'monto_inicial_default' => 100.00
        ];

        if (!$this->db || $this->db->connect_error) {
            return $default;
        }

        $res = $this->db->query("SELECT * FROM config_caja LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'caja_activa' => !empty($row['caja_activa']) && !empty($row['id_corte_actual']),
                'id_corte_actual' => $row['id_corte_actual'] ? (int)$row['id_corte_actual'] : null,
                'fecha_ultimo_corte' => $row['fecha_ultimo_corte'] ?: null,
                'corte_automatico_habilitado' => (bool)$row['corte_automatico_habilitado'],
                'hora_corte_automatico' => (string)$row['hora_corte_automatico'],
                'monto_inicial_default' => (float)$row['monto_inicial_default']
            ];
        }

        return $default;
    }

    public function updateConfig(bool $habilitado, string $hora, float $montoDefault): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $hab = $habilitado ? 1 : 0;
        $stmt = $this->db->prepare("UPDATE config_caja SET corte_automatico_habilitado = ?, hora_corte_automatico = ?, monto_inicial_default = ? WHERE id = 1");
        if (!$stmt) return false;

        $stmt->bind_param("isd", $hab, $hora, $montoDefault);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function isCashRegisterActive(): bool
    {
        $cfg = $this->getConfig();
        return $cfg['caja_activa'] && !empty($cfg['id_corte_actual']);
    }

    public function getCurrentCut(): ?array
    {
        $cfg = $this->getConfig();
        if (!$cfg['caja_activa'] || empty($cfg['id_corte_actual'])) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT c.*, u.nombre AS nombre_usuario 
                                   FROM cortes c 
                                   LEFT JOIN usuarios u ON c.id_usuario = u.id 
                                   WHERE c.id = ? LIMIT 1");
        if (!$stmt) return null;

        $id = $cfg['id_corte_actual'];
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $cut = $res->fetch_assoc();
        $stmt->close();

        return $cut ? $this->mapCutRow($cut) : null;
    }

    public function calculateTotals(int $idCorte, float $montoInicial = 0.0): array
    {
        $totals = [
            'monto_inicial' => $montoInicial,
            'total_ingresos' => 0.0,
            'total_egresos' => 0.0,
            'num_ingresos' => 0,
            'num_egresos' => 0,
            'monto_esperado' => $montoInicial
        ];

        if (!$this->db || $this->db->connect_error) {
            return $totals;
        }

        $stmt = $this->db->prepare("SELECT 
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE 0 END), 0) AS total_ingresos,
            COALESCE(SUM(CASE WHEN tipo_movimiento = 'egreso' THEN monto ELSE 0 END), 0) AS total_egresos,
            COALESCE(COUNT(CASE WHEN tipo_movimiento = 'ingreso' THEN 1 END), 0) AS num_ingresos,
            COALESCE(COUNT(CASE WHEN tipo_movimiento = 'egreso' THEN 1 END), 0) AS num_egresos
            FROM movimientos_caja WHERE id_corte = ?");

        if ($stmt) {
            $stmt->bind_param("i", $idCorte);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $ingresos = (float)$row['total_ingresos'];
                $egresos = (float)$row['total_egresos'];
                $totals['total_ingresos'] = $ingresos;
                $totals['total_egresos'] = $egresos;
                $totals['num_ingresos'] = (int)$row['num_ingresos'];
                $totals['num_egresos'] = (int)$row['num_egresos'];
                $totals['monto_esperado'] = $montoInicial + $ingresos - $egresos;
            }
        }

        return $totals;
    }

    public function getMovements(int $idCorte): array
    {
        $movements = [];
        if (!$this->db || $this->db->connect_error) return $movements;

        $stmt = $this->db->prepare("SELECT m.*, u.nombre AS nombre_usuario 
                                   FROM movimientos_caja m 
                                   LEFT JOIN usuarios u ON m.id_usuario = u.id 
                                   WHERE m.id_corte = ? 
                                   ORDER BY m.fecha_hora DESC");
        if ($stmt) {
            $stmt->bind_param("i", $idCorte);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $movements[] = [
                    'id' => (int)$row['id'],
                    'id_corte' => (int)$row['id_corte'],
                    'tipo_movimiento' => (string)$row['tipo_movimiento'],
                    'concepto' => (string)$row['concepto'],
                    'monto' => (float)$row['monto'],
                    'metodo_pago' => $row['metodo_pago'] ?: 'Efectivo',
                    'referencia' => $row['referencia'] ?: null,
                    'id_venta' => $row['id_venta'] ? (int)$row['id_venta'] : null,
                    'nombre_usuario' => $row['nombre_usuario'] ?: 'Admin',
                    'fecha_hora' => (string)$row['fecha_hora'],
                    'notas' => $row['notas'] ?: null
                ];
            }
            $stmt->close();
        }

        return $movements;
    }

    public function openCashRegister(float $montoInicial, int $idUsuario, ?string $notas = null): array
    {
        if ($this->isCashRegisterActive()) {
            throw new Exception("Ya existe una caja activa. Debe cerrarla antes de iniciar una nueva.");
        }

        $this->db->begin_transaction();
        try {
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');
            $movimientos = [
                'apertura' => [
                    'fecha_hora' => date('Y-m-d H:i:s'),
                    'monto' => $montoInicial,
                    'usuario' => $idUsuario,
                    'notas' => $notas
                ],
                'ingresos' => [],
                'egresos' => []
            ];
            $movJson = json_encode($movimientos, JSON_UNESCAPED_UNICODE);

            $stmt = $this->db->prepare("INSERT INTO cortes (fecha, hora, tipo_movimiento, movimientos_json, comandas_ids, monto_inicial, id_usuario, notas) 
                                       VALUES (?, ?, 'inicio', ?, '', ?, ?, ?)");
            $stmt->bind_param("sssdis", $fecha, $hora, $movJson, $montoInicial, $idUsuario, $notas);
            $stmt->execute();
            $idCorte = (int)$this->db->insert_id;
            $stmt->close();

            // Registrar movimiento de apertura
            $this->addMovement($idCorte, 'apertura', 'Apertura de caja', $montoInicial, 'Efectivo', $idUsuario, $notas);

            // Actualizar config_caja
            $stmtCfg = $this->db->prepare("UPDATE config_caja SET caja_activa = 1, id_corte_actual = ?, fecha_ultimo_corte = NOW() WHERE id = 1");
            $stmtCfg->bind_param("i", $idCorte);
            $stmtCfg->execute();
            $stmtCfg->close();

            $this->db->commit();

            return [
                'success' => true,
                'id_corte' => $idCorte,
                'message' => 'Caja iniciada exitosamente.'
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function closeCashRegister(int $idUsuario, float $montoFinal, ?string $notas = null): array
    {
        if (!$this->isCashRegisterActive()) {
            throw new Exception("No hay una caja activa actualmente para cerrar.");
        }

        $cfg = $this->getConfig();
        $idCorte = $cfg['id_corte_actual'];
        $cut = $this->getCurrentCut();
        if (!$cut) {
            throw new Exception("No se encontró la información del corte actual.");
        }

        $montoInicial = $cut['monto_inicial'];
        $totals = $this->calculateTotals($idCorte, $montoInicial);
        $montoEsperado = $totals['monto_esperado'];
        $diferencia = $montoFinal - $montoEsperado;
        $totalIngresos = $totals['total_ingresos'];
        $totalEgresos = $totals['total_egresos'];

        $this->db->begin_transaction();
        try {
            // Actualizar corte a tipo 'fin'
            $stmt = $this->db->prepare("UPDATE cortes SET 
                tipo_movimiento = 'fin',
                monto_final = ?,
                total_ingresos = ?,
                total_egresos = ?,
                diferencia = ?,
                notas = CONCAT(COALESCE(notas, ''), '\n--- CIERRE ---\n', ?)
                WHERE id = ?");
            $stmt->bind_param("ddddsi", $montoFinal, $totalIngresos, $totalEgresos, $diferencia, $notas, $idCorte);
            $stmt->execute();
            $stmt->close();

            // Registrar movimiento de cierre
            $this->addMovement($idCorte, 'cierre', 'Cierre de caja', $montoFinal, 'Efectivo', $idUsuario, $notas);

            // Actualizar config_caja
            $this->db->query("UPDATE config_caja SET caja_activa = 0, id_corte_actual = NULL, fecha_ultimo_corte = NOW() WHERE id = 1");

            $this->db->commit();

            return [
                'success' => true,
                'id_corte' => $idCorte,
                'monto_esperado' => $montoEsperado,
                'monto_declarado' => $montoFinal,
                'diferencia' => $diferencia,
                'message' => 'Caja cerrada y corte guardado exitosamente.'
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function addMovement(
        int $idCorte,
        string $tipo,
        string $concepto,
        float $monto,
        ?string $metodoPago = 'Efectivo',
        int $idUsuario = 1,
        ?string $notas = null
    ): int {
        $stmt = $this->db->prepare("INSERT INTO movimientos_caja 
            (id_corte, tipo_movimiento, concepto, monto, metodo_pago, id_usuario, fecha_hora, notas) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar movimiento: " . $this->db->error);
        }

        $stmt->bind_param("issdsis", $idCorte, $tipo, $concepto, $monto, $metodoPago, $idUsuario, $notas);
        $stmt->execute();
        $id = (int)$this->db->insert_id;
        $stmt->close();
        return $id;
    }

    public function getHistory(?string $desde = null, ?string $hasta = null, int $limit = 50): array
    {
        $history = [];
        if (!$this->db || $this->db->connect_error) return $history;

        $sql = "SELECT c.*, u.nombre AS nombre_usuario 
                FROM cortes c 
                LEFT JOIN usuarios u ON c.id_usuario = u.id 
                WHERE c.tipo_movimiento = 'fin'";

        $params = [];
        $types = "";

        if (!empty($desde)) {
            $sql .= " AND c.fecha >= ?";
            $params[] = $desde;
            $types .= "s";
        }

        if (!empty($hasta)) {
            $sql .= " AND c.fecha <= ?";
            $params[] = $hasta;
            $types .= "s";
        }

        $sql .= " ORDER BY c.fecha DESC, c.hora DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $history[] = $this->mapCutRow($row);
            }
            $stmt->close();
        }

        return $history;
    }

    public function getCutDetail(int $idCorte): ?array
    {
        if (!$this->db || $this->db->connect_error) return null;

        $stmt = $this->db->prepare("SELECT c.*, u.nombre AS nombre_usuario 
                                   FROM cortes c 
                                   LEFT JOIN usuarios u ON c.id_usuario = u.id 
                                   WHERE c.id = ? LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param("i", $idCorte);
        $stmt->execute();
        $cut = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cut) return null;

        $mappedCut = $this->mapCutRow($cut);
        $movements = $this->getMovements($idCorte);
        $totals = $this->calculateTotals($idCorte, $mappedCut['monto_inicial']);

        return [
            'corte' => $mappedCut,
            'totales' => $totals,
            'movimientos' => $movements
        ];
    }

    private function mapCutRow(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'fecha' => (string)$row['fecha'],
            'hora' => (string)$row['hora'],
            'tipo_movimiento' => (string)$row['tipo_movimiento'],
            'monto_inicial' => (float)$row['monto_inicial'],
            'monto_final' => $row['monto_final'] !== null ? (float)$row['monto_final'] : null,
            'total_ingresos' => $row['total_ingresos'] !== null ? (float)$row['total_ingresos'] : null,
            'total_egresos' => $row['total_egresos'] !== null ? (float)$row['total_egresos'] : null,
            'diferencia' => $row['diferencia'] !== null ? (float)$row['diferencia'] : null,
            'id_usuario' => $row['id_usuario'] ? (int)$row['id_usuario'] : null,
            'nombre_usuario' => $row['nombre_usuario'] ?: 'Administrador',
            'notas' => $row['notas'] ?: null
        ];
    }
}
