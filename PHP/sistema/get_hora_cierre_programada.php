<?php
// get_hora_cierre_programada.php
header('Content-Type: application/json');
require_once __DIR__ . '/admin/dist/db_config_dual.php';

try {
    // Obtener el último corte abierto (sin hora_cierre)
    $sql = "SELECT hora_cierre_programada,tipo_movimiento FROM cortes ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['tipo_movimiento'] == 'inicio') {
            echo json_encode([
                'success' => true,
                'hora_cierre_programada' => $row['hora_cierre_programada']
            ]);            
        }
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No se encontró corte abierto.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error: ' . $e->getMessage()
    ]);
}
if (isset($conn)) {
    $conn->close();
}
