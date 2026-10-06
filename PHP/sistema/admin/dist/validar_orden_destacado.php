<?php
header('Content-Type: application/json');
require_once 'db_config_dual.php';

try {
    $orden = isset($_GET['orden']) ? intval($_GET['orden']) : 0;
    $id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : null;
    
    if ($orden <= 0) {
        echo json_encode([
            'success' => false,
            'disponible' => false,
            'message' => 'Orden inválido'
        ]);
        exit;
    }
    
    // Verificar si el orden ya está ocupado (excluyendo el producto actual si es edición)
    $sql = "SELECT id_producto FROM productos WHERE orden_destacado = ? AND destacado = 1";
    
    if ($id_producto) {
        $sql .= " AND id_producto != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $orden, $id_producto);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $orden);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $disponible = $result->num_rows === 0;
    
    echo json_encode([
        'success' => true,
        'disponible' => $disponible,
        'mensaje' => $disponible ? 'Orden disponible' : 'Orden ya ocupado'
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'disponible' => false,
        'message' => 'Error al validar orden: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
