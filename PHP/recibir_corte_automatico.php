<?php
/**
 * recibir_corte_automatico.php
 *
 * Endpoint que recibe la información de un corte automático desde
 * la máquina local y la persiste en la BD de la nube.
 *
 * Método:  POST
 * Headers: X-Api-Key: <clave>
 * Body:    JSON
 *   {
 *     "tipo": "corte_automatico",
 *     "timestamp": "YYYY-MM-DD HH:MM:SS",
 *     "corte_cerrado": { <fila completa de la tabla cortes> },
 *     "resultado_cierre":   { <datos del cierre> },
 *     "resultado_apertura": { <datos de la apertura> }
 *   }
 *
 * Respuesta JSON:
 *   { "success": true,  "mensaje": "...", "id_corte": <int> }
 *   { "success": false, "error": "..." }
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json; charset=utf-8');


// ──────────────────────────────────────────
// 1. Método HTTP
// ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// ──────────────────────────────────────────
// 2. Leer y validar el body JSON
// ──────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || ($data['tipo'] ?? '') !== 'corte_automatico') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payload inválido o tipo incorrecto']);
    exit;
}

$corte            = $data['corte_cerrado']      ?? null;
$resultado_cierre = $data['resultado_cierre']   ?? [];
$resultado_ap     = $data['resultado_apertura'] ?? [];
$timestamp        = $data['timestamp']          ?? date('Y-m-d H:i:s');

if (!$corte || empty($corte['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos del corte ausentes o sin ID']);
    exit;
}

// ──────────────────────────────────────────
// 4. Conexión a BD nube
// ──────────────────────────────────────────
try {
    $conn = new mysqli(
        'cpanel.colegos.com.mx',
        'colegos_vending',
        'IfbUK2ClF~bV',
        'colegos_vending',
        3306
    );
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a BD: ' . $e->getMessage()]);
    exit;
}

// ──────────────────────────────────────────
// 5. UPSERT del corte en la tabla cortes
// ──────────────────────────────────────────
try {
    // Extraer campos del corte recibido (solo los que existen en el schema)
    $id              = (int)   $corte['id'];
    $fecha           =         $corte['fecha']           ?? date('Y-m-d');
    $hora            =         $corte['hora']            ?? date('H:i:s');
    $tipo_movimiento =         $corte['tipo_movimiento'] ?? 'fin';
    $movimientos_json=         $corte['movimientos_json']?? null;
    $comandas_ids    =         $corte['comandas_ids']    ?? '';
    $monto_inicial   = (float) ($corte['monto_inicial']  ?? 0);
    $monto_final     = (float) ($corte['monto_final']    ?? $resultado_cierre['monto_declarado'] ?? 0);
    $total_ingresos  = (float) ($corte['total_ingresos'] ?? $resultado_cierre['total_ingresos']  ?? 0);
    $total_egresos   = (float) ($corte['total_egresos']  ?? $resultado_cierre['total_egresos']   ?? 0);
    $diferencia      = (float) ($corte['diferencia']     ?? $resultado_cierre['diferencia']      ?? 0);
    $id_usuario      = isset($corte['id_usuario']) ? (int)$corte['id_usuario'] : null;
    $notas           =         $corte['notas']           ?? null;

    $sql = "INSERT INTO cortes (
                id, fecha, hora, tipo_movimiento, movimientos_json, comandas_ids,
                monto_inicial, monto_final, total_ingresos, total_egresos,
                diferencia, id_usuario, notas, sincronizado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                tipo_movimiento  = VALUES(tipo_movimiento),
                monto_final      = VALUES(monto_final),
                total_ingresos   = VALUES(total_ingresos),
                total_egresos    = VALUES(total_egresos),
                diferencia       = VALUES(diferencia),
                movimientos_json = VALUES(movimientos_json),
                notas            = VALUES(notas),
                sincronizado     = 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando query: ' . $conn->error);
    }

    $stmt->bind_param(
        "isssssdddddis",
        $id, $fecha, $hora, $tipo_movimiento, $movimientos_json,
        $comandas_ids, $monto_inicial, $monto_final,
        $total_ingresos, $total_egresos, $diferencia,
        $id_usuario, $notas
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al guardar corte: ' . $stmt->error);
    }
    $stmt->close();

    // ──────────────────────────────────────────
    // 6. Log de recepción
    // ──────────────────────────────────────────
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $log_msg = "[{$timestamp}] Corte automático recibido - ID corte: {$id}"
             . " | Cierre: $" . number_format($monto_final, 2)
             . " | Diferencia: $" . number_format($diferencia, 2) . "\n";
    @file_put_contents($log_dir . '/cortes_nube.log', $log_msg, FILE_APPEND);

    echo json_encode([
        'success'   => true,
        'mensaje'   => 'Corte recibido y guardado correctamente',
        'id_corte'  => $id,
        'timestamp' => $timestamp,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("[recibir_corte_automatico] " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
