<?php
header('Content-Type: application/json');
require_once 'db_config_dual.php';

try {
    // Obtener el máximo orden actual
    $sql = "SELECT MAX(orden_destacado) as max_orden FROM productos WHERE destacado = 1";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $max_orden = $row['max_orden'] ?? 0;
        $proximo_orden = $max_orden + 1;
        
        echo json_encode([
            'success' => true,
            'proximo_orden' => $proximo_orden
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'proximo_orden' => 1
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener orden: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
