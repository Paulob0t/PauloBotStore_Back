<?php
// eliminar_subcategoria.php
require_once 'db_config_dual.php';
header('Content-Type: application/json');

$id_subcategoria = intval($_POST['id_subcategoria'] ?? 0);

if ($id_subcategoria <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de subcategoría inválido']);
    exit;
}

// Eliminar subcategoría
$stmt = $conn->prepare('DELETE FROM subcategorias WHERE id_subcategoria = ?');
$stmt->bind_param('i', $id_subcategoria);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar subcategoría']);
}

$stmt->close();
$conn->close();
?>
