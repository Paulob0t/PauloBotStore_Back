<?php
header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode([
        'ok' => false,
        'error' => 'No se encontro db_config_dual.php',
        'paths_checked' => $configCandidates
    ]);
    exit;
}

include $configPath;

$tablas = [
    'categorias',
    'configuracion_empresa',
    'config_caja',
    'cortes',
    'page_titles',
    'productos',
    'subcategorias'
];

// POST: el script local confirma que ya aplico los cambios de la nube
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $input   = json_decode($rawBody, true);
    $ids     = $input['marcar_sincronizados'] ?? [];

    if (!is_array($ids) || empty($ids)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Se espera {"marcar_sincronizados":[...]}']);
        exit;
    }

    $ids = array_values(array_filter(array_map('intval', $ids), static fn($id) => $id > 0));
    if (empty($ids)) {
        echo json_encode(['ok' => true, 'marcados' => 0]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "UPDATE sincronizacion_log
            SET sincronizado = 1, fecha_sincronizado = NOW()
            WHERE origen = 'NUBE' AND id_sync IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $conn->error]);
        exit;
    }

    $types = str_repeat('i', count($ids));
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $marcados = $stmt->affected_rows;
    $stmt->close();

    echo json_encode(['ok' => true, 'marcados' => $marcados]);
    exit;
}

// GET: tablas completas + cola incremental generada en la nube
$data = ['ok' => true];

foreach ($tablas as $tabla) {
    $result = $conn->query("SELECT * FROM `$tabla`");
    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Error al consultar tabla',
            'tabla' => $tabla,
            'detalle' => $conn->error
        ]);
        exit;
    }
    $data[$tabla] = $result->fetch_all(MYSQLI_ASSOC);
}

$pendientes = [];
$resP = $conn->query(
    "SELECT id_sync, tabla, accion, id_registro, datos
     FROM sincronizacion_log
     WHERE origen = 'NUBE' AND sincronizado = 0
     ORDER BY id_sync ASC
     LIMIT 100"
);

if ($resP) {
    while ($row = $resP->fetch_assoc()) {
        $decoded = json_decode($row['datos'] ?? '{}', true);
        $pendientes[] = [
            'id_sync'     => (int) $row['id_sync'],
            'tabla'       => $row['tabla'],
            'accion'      => $row['accion'],
            'id_registro' => (int) $row['id_registro'],
            'datos'       => is_array($decoded) ? $decoded : [],
        ];
    }
}

$data['pendientes_nube'] = $pendientes;

echo json_encode($data);
