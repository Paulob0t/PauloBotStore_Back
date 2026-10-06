<?php
/**
 * get_producto_imagenes.php
 * 
 * Endpoint para obtener campos pesados de un producto específico (imágenes y descripción)
 * Evita cargar estos datos para todos los productos en memoria
 */

header('Content-Type: application/json');
include "db_config_dual.php";

// Verificar que se recibió el ID del producto
if (!isset($_GET['id_producto']) || !is_numeric($_GET['id_producto'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de producto inválido']);
    exit;
}

$id_producto = intval($_GET['id_producto']);

// Consultar campos pesados: descripción e imágenes
$stmt = $conn->prepare("SELECT descripcion, imagen_principal, imagen_secundaria_1, imagen_secundaria_2, imagen_secundaria_3 FROM productos WHERE id_producto = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'imagenes' => $row
    ]);
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Producto no encontrado'
    ]);
}

$stmt->close();
$conn->close();
?>
