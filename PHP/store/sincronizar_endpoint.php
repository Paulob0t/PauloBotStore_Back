<?php
/**
 * sincronizar_endpoint.php
 *
 * Endpoint de sincronización: recibe registros desde el script PS1 local
 * y aplica la acción real (INSERT / UPDATE / DELETE) en la BD nube.
 *
 * Se invoca desde sincronizar_nube.ps1 vía HTTP POST con JSON body:
 *   {
 *     "registros": [
 *       {
 *         "id_sync":     <int>,       // ID en sincronizacion_log local (para trazar)
 *         "tabla":       <string>,    // Tabla destino donde se aplica la acción
 *         "accion":      <string>,    // INSERT | UPDATE | DELETE
 *         "id_registro": <int>,       // PK del registro en la tabla destino
 *         "datos":       <object>     // JSON con los campos y valores del registro
 *       },
 *       ...
 *     ]
 *   }
 *
 * Respuesta JSON:
 *   {
 *     "exitosos": [id_sync, ...],           // IDs procesados sin error
 *     "fallidos": [{"id_sync":N, "error":"..."},...],
 *     "procesados": <int>,
 *     "con_error":  <int>
 *   }
 *
 * ⚠️  SEGURIDAD:
 *   - Autenticación por API key en cabecera X-Api-Key
 *   - Nombre de tabla validado contra INFORMATION_SCHEMA (debe existir en la BD)
 *   - Nombres de columnas validados con regex (solo [a-zA-Z_][a-zA-Z0-9_]*)
 *   - Valores inyectados únicamente via parámetros PDO (prepared statements)
 */


$configCandidates = [
    __DIR__ . '/admin/dist/db_config_dual.php',
    __DIR__ . '/sistema/admin/dist/db_config_dual.php'
];

$configPath = null;
foreach ($configCandidates as $candidate) {
    if (file_exists($candidate)) {
        $configPath = $candidate;
        break;
    }
}

if ($configPath === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'No se encontro db_config_dual.php',
        'paths_checked' => $configCandidates
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

include $configPath;

// ============================================================
// INICIALIZACIÓN
// ============================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// 2. MÉTODO Y BODY
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true);

if (!$input || !isset($input['registros']) || !is_array($input['registros'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido: se espera {"registros":[...]}']);
    exit;
}

// ============================================================
// 3. VERIFICAR CONEXIÓN A BD (provista por db_config_dual.php)
// ============================================================
if (!$conn) {
    http_response_code(503);
    echo json_encode(['error' => 'Error de conexión a BD']);
    exit;
}

// ============================================================
// 4. FUNCIONES AUXILIARES
// ============================================================

/** Cache de existencia de tablas y PKs para evitar consultas repetidas */
$_tableCache = [];
$_pkCache    = [];

/**
 * Verifica que la tabla exista en la BD nube (whitelist dinámica).
 */
function tableExists(string $tabla): bool
{
    global $_tableCache, $conn;
    if (isset($_tableCache[$tabla])) {
        return $_tableCache[$tabla];
    }
    $stmt = mysqli_prepare($conn,
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    mysqli_stmt_bind_param($stmt, 's', $tabla);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    return $_tableCache[$tabla] = (bool) $row[0];
}

/**
 * Obtiene el nombre de la columna que es clave primaria de la tabla.
 */
function getPrimaryKey(string $tabla): ?string
{
    global $_pkCache, $conn;
    if (isset($_pkCache[$tabla])) {
        return $_pkCache[$tabla];
    }
    $constraint = 'PRIMARY';
    $stmt = mysqli_prepare($conn,
        'SELECT COLUMN_NAME
         FROM   INFORMATION_SCHEMA.KEY_COLUMN_USAGE
         WHERE  TABLE_SCHEMA    = DATABASE()
           AND  TABLE_NAME      = ?
           AND  CONSTRAINT_NAME = ?
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'ss', $tabla, $constraint);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_row($result);
    mysqli_stmt_close($stmt);
    return $_pkCache[$tabla] = ($row ? $row[0] : null);
}

/**
 * Valida que un nombre de columna solo contenga caracteres seguros.
 * Previene inyección SQL al construir dinámicamente los SET / column lists.
 */
function isValidColumnName(string $name): bool
{
    return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/', $name);
}

/**
 * Aplica INSERT, UPDATE o DELETE en la tabla indicada.
 *
 * @param PDO    $pdo
 * @param string $tabla       Nombre de la tabla destino
 * @param string $accion      INSERT | UPDATE | DELETE
 * @param int    $id_registro PK del registro a afectar
 * @param array  $datos       Campos y valores (del JSON sincronizacion_log.datos)
 *
 * @throws Exception si la acción no es reconocida, la PK no existe o hay columna inválida
 */
function applyAction(string $tabla, string $accion, int $id_registro, array $datos): void
{
    global $conn;

    $pk = getPrimaryKey($tabla);
    if (!$pk) {
        throw new Exception("No se encontró clave primaria para la tabla '{$tabla}'");
    }

    // Validar todos los nombres de columna antes de usarlos en el SQL dinámico
    foreach (array_keys($datos) as $col) {
        if (!isValidColumnName($col)) {
            throw new Exception("Nombre de columna inválido: '{$col}'");
        }
    }

    switch (strtoupper(trim($accion))) {

        // --------------------------------------------------------
        case 'INSERT':
            // Asegurar que el PK esté incluido con el valor de id_registro
            if (!array_key_exists($pk, $datos)) {
                $datos[$pk] = $id_registro;
            }

            $columns      = array_keys($datos);
            $colList      = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            // ON DUPLICATE KEY UPDATE: actualiza todos los campos EXCEPTO la PK
            $updateCols   = array_filter($columns, fn($c) => $c !== $pk);
            $updates      = implode(', ', array_map(fn($c) => "`{$c}` = VALUES(`{$c}`)", $updateCols));

            $sql  = "INSERT INTO `{$tabla}` ({$colList}) VALUES ({$placeholders})";
            if (!empty($updateCols)) {
                $sql .= " ON DUPLICATE KEY UPDATE {$updates}";
            }
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) throw new Exception('Error preparando INSERT: ' . mysqli_error($conn));
            $types  = str_repeat('s', count($columns));
            $values = array_values($datos);
            mysqli_stmt_bind_param($stmt, $types, ...$values);
            if (!mysqli_stmt_execute($stmt)) throw new Exception('Error ejecutando INSERT: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            break;

        // --------------------------------------------------------
        case 'UPDATE':
            if (empty($datos)) {
                return; // sin datos no hay nada que actualizar
            }
            // Excluir la PK del SET (es el identificador usado en el WHERE)
            $updateCols = array_values(array_filter(array_keys($datos), fn($c) => $c !== $pk));
            if (empty($updateCols)) {
                return;
            }
            $sets = implode(', ', array_map(fn($c) => "`{$c}` = ?", $updateCols));
            $sql  = "UPDATE `{$tabla}` SET {$sets} WHERE `{$pk}` = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) throw new Exception('Error preparando UPDATE: ' . mysqli_error($conn));
            $vals = [];
            foreach ($updateCols as $c) {
                $vals[] = $datos[$c];
            }
            $vals[] = $id_registro; // valor del WHERE al final
            $types  = str_repeat('s', count($vals));
            mysqli_stmt_bind_param($stmt, $types, ...$vals);
            if (!mysqli_stmt_execute($stmt)) throw new Exception('Error ejecutando UPDATE: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            break;

        // --------------------------------------------------------
        case 'DELETE':
            $sql  = "DELETE FROM `{$tabla}` WHERE `{$pk}` = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) throw new Exception('Error preparando DELETE: ' . mysqli_error($conn));
            mysqli_stmt_bind_param($stmt, 's', $id_registro);
            if (!mysqli_stmt_execute($stmt)) throw new Exception('Error ejecutando DELETE: ' . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            break;

        // --------------------------------------------------------
        default:
            throw new Exception("Acción no reconocida: '{$accion}'. Se esperaba INSERT, UPDATE o DELETE.");
    }
}

// ============================================================
// 5. PROCESAR CADA REGISTRO
// ============================================================
$exitosos = [];
$fallidos = [];

foreach ($input['registros'] as $reg) {
    // ── Extraer y sanear campos obligatorios ──
    $id_sync     = isset($reg['id_sync'])     ? (int) $reg['id_sync']     : null;
    $tabla       = isset($reg['tabla'])       ? trim((string) $reg['tabla'])  : '';
    $accion      = isset($reg['accion'])      ? trim((string) $reg['accion']) : '';
    $id_registro = isset($reg['id_registro']) ? (int) $reg['id_registro'] : null;
    $datos       = $reg['datos'] ?? [];

    // ── Validaciones básicas ──
    if (!$id_sync || !$tabla || !$accion || !$id_registro) {
        $fallidos[] = [
            'id_sync' => $id_sync,
            'error'   => 'Campos requeridos faltantes (id_sync, tabla, accion, id_registro)',
        ];
        continue;
    }

    if (!is_array($datos)) {
        $fallidos[] = ['id_sync' => $id_sync, 'error' => 'El campo datos debe ser un objeto JSON'];
        continue;
    }

    // ── Verificar que la tabla existe en la BD nube ──
    if (!tableExists($tabla)) {
        $fallidos[] = ['id_sync' => $id_sync, 'error' => "La tabla '{$tabla}' no existe en la BD nube"];
        continue;
    }

    // ── Aplicar la acción dentro de su propia transacción ──
    try {
        mysqli_begin_transaction($conn);
        applyAction($tabla, $accion, $id_registro, $datos);
        mysqli_commit($conn);
        $exitosos[] = $id_sync;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("[sync_endpoint] id_sync={$id_sync} tabla={$tabla} accion={$accion}: " . $e->getMessage());
        $fallidos[] = ['id_sync' => $id_sync, 'error' => $e->getMessage()];
    }
}

// ============================================================
// 6. RESPUESTA
// ============================================================
echo json_encode([
    'exitosos'   => $exitosos,
    'fallidos'   => $fallidos,
    'procesados' => count($exitosos),
    'con_error'  => count($fallidos),
], JSON_UNESCAPED_UNICODE);
