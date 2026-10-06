<?php
// eliminar_categoria.php
require_once 'db_config_dual.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$id_categoria = intval($data['id_categoria'] ?? 0);

if ($id_categoria <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de categoría inválido']);
    exit;
}

// Desactivar checks de FK para evitar errores si hay dependencias
$conn->query('SET FOREIGN_KEY_CHECKS=0');

// Eliminar subcategorías primero (por FK)
$stmt = $conn->prepare('DELETE FROM subcategorias WHERE id_categoria = ?');
$stmt->bind_param('i', $id_categoria);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
    echo json_encode(['success' => false, 'message' => 'Error al eliminar subcategorías']);
    exit;
}
$stmt->close();

// Eliminar la categoría
$stmt = $conn->prepare('DELETE FROM categorias WHERE id_categoria = ?');
$stmt->bind_param('i', $id_categoria);
if (!$stmt->execute()) {
    $stmt->close();
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
    echo json_encode(['success' => false, 'message' => 'Error al eliminar categoría']);
    exit;
}
$stmt->close();

// Reactivar checks de FK
$conn->query('SET FOREIGN_KEY_CHECKS=1');

echo json_encode(['success' => true]);
$conn->close();
?>
