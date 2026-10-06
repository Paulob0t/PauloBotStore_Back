<?php
// actualizar_categoria_y_subcategoria.php
require_once 'db_config_dual.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id_categoria = intval($data['id_categoria'] ?? 0);
$nombre_categoria = trim($data['nombre_categoria'] ?? '');
$imagen_categoria = $data['imagen_categoria'] ?? null;

$id_subcategoria = intval($data['id_subcategoria'] ?? 0);
$nombre_subcategoria = trim($data['nombre_subcategoria'] ?? '');
$imagen_subcategoria = $data['imagen_subcategoria'] ?? null;

// Validar la imagen si se proporciona
if ($imagen_categoria !== null) {
    if (!empty($imagen_categoria) && !preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imagen_categoria)) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagen no válido']);
        exit;
    }
}

if ($id_categoria <= 0 || $nombre_categoria === '') {
    echo json_encode(['success' => false, 'message' => 'Datos de categoría inválidos']);
    exit;
}

// Actualizar nombre y/o imagen de la categoría
if ($imagen_categoria !== null) {
    $stmt = $conn->prepare('UPDATE categorias SET nombre_categoria = ?, imagen_categoria = ? WHERE id_categoria = ?');
    $stmt->bind_param('ssi', $nombre_categoria, $imagen_categoria, $id_categoria);
} else {
    $stmt = $conn->prepare('UPDATE categorias SET nombre_categoria = ? WHERE id_categoria = ?');
    $stmt->bind_param('si', $nombre_categoria, $id_categoria);
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar categoría']);
    exit;
}
$stmt->close();

// Si se envía subcategoría, actualizar su nombre, relación e imagen
if ($id_subcategoria > 0 && $nombre_subcategoria !== '') {
    if ($imagen_subcategoria !== null) {
        // Actualizar con imagen
        $stmt = $conn->prepare('UPDATE subcategorias SET nombre_subcategoria = ?, id_categoria = ?, imagen_subcategoria = ? WHERE id_subcategoria = ?');
        $stmt->bind_param('sisi', $nombre_subcategoria, $id_categoria, $imagen_subcategoria, $id_subcategoria);
    } else {
        // Actualizar sin imagen
        $stmt = $conn->prepare('UPDATE subcategorias SET nombre_subcategoria = ?, id_categoria = ? WHERE id_subcategoria = ?');
        $stmt->bind_param('sii', $nombre_subcategoria, $id_categoria, $id_subcategoria);
    }
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar subcategoría']);
        exit;
    }
    $stmt->close();
}

echo json_encode(['success' => true]);
$conn->close();
