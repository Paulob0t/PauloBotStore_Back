<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

function respondJson(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/db_config_dual.php';

    if (!isset($conn) || !$conn) {
        respondJson([
            'success' => false,
            'error' => 'No se pudo conectar a la base de datos'
        ], 500);
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === false || trim($rawBody) === '') {
        respondJson([
            'success' => false,
            'error' => 'Body JSON vacío'
        ], 400);
    }

    $input = json_decode($rawBody, true);
    if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
        respondJson([
            'success' => false,
            'error' => 'JSON inválido: ' . json_last_error_msg()
        ], 400);
    }

    $nombre_empresa = trim((string)($input['nombre_empresa'] ?? ''));
    if ($nombre_empresa === '') {
        respondJson([
            'success' => false,
            'error' => 'El nombre de la empresa es obligatorio'
        ], 422);
    }

    $direccion = trim((string)($input['direccion'] ?? ''));
    $ciudad = trim((string)($input['ciudad'] ?? ''));
    $estado = trim((string)($input['estado'] ?? ''));
    $telefono = trim((string)($input['telefono'] ?? ''));
    $rfc = strtoupper(trim((string)($input['rfc'] ?? '')));
    $website = trim((string)($input['website'] ?? 'www.vendigbox.com'));

    $sql = "INSERT INTO configuracion_empresa (id, nombre_empresa, direccion, ciudad, estado, telefono, rfc, website, fecha_actualizacion)
            VALUES (1, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                nombre_empresa = VALUES(nombre_empresa),
                direccion = VALUES(direccion),
                ciudad = VALUES(ciudad),
                estado = VALUES(estado),
                telefono = VALUES(telefono),
                rfc = VALUES(rfc),
                website = VALUES(website),
                fecha_actualizacion = NOW()";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        respondJson([
            'success' => false,
            'error' => 'Error al preparar consulta: ' . mysqli_error($conn)
        ], 500);
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssss',
        $nombre_empresa,
        $direccion,
        $ciudad,
        $estado,
        $telefono,
        $rfc,
        $website
    );

    if (!mysqli_stmt_execute($stmt)) {
        respondJson([
            'success' => false,
            'error' => 'Error al guardar: ' . mysqli_stmt_error($stmt)
        ], 500);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    respondJson([
        'success' => true,
        'message' => 'Configuración guardada correctamente'
    ]);
} catch (Throwable $e) {
    respondJson([
        'success' => false,
        'error' => 'Error interno: ' . $e->getMessage()
    ], 500);
}
