<?php
require_once 'db_config_dual.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

$data = json_decode(file_get_contents('php://input'), true);
$id_categoria = isset($data['id_categoria']) ? intval($data['id_categoria']) : 0;
$categoria = trim($data['categoria'] ?? '');
$subcategorias = $data['subcategorias'] ?? [];
$imagen_categoria = $data['imagen_categoria'] ?? '';
$imagen_subcategoria = $data['imagen_subcategoria'] ?? '';
$subcategoriasNuevas = [];

function obtenerSubcategoriasDeCategoria($conn, $id_categoria) {
    $lista = [];
    $stmt = $conn->prepare('SELECT id_subcategoria, nombre_subcategoria FROM subcategorias WHERE id_categoria = ? ORDER BY id_subcategoria');
    $stmt->bind_param('i', $id_categoria);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $lista[] = [
            'id' => (int)$row['id_subcategoria'],
            'nombre' => $row['nombre_subcategoria']
        ];
    }
    $stmt->close();
    return $lista;
}

function obtenerNombreCategoria($conn, $id_categoria) {
    $stmt = $conn->prepare('SELECT nombre_categoria FROM categorias WHERE id_categoria = ?');
    $stmt->bind_param('i', $id_categoria);
    $stmt->execute();
    $stmt->bind_result($nombre);
    $stmt->fetch();
    $stmt->close();
    return $nombre ?? '';
}

// Validar la imagen si se proporciona
if (!empty($imagen_categoria)) {
    if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imagen_categoria)) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagen no válido']);
        exit;
    }
}

if ($id_categoria > 0) {
    $stmt = $conn->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = ?');
    $stmt->bind_param('i', $id_categoria);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'La categoría no existe']);
        $stmt->close();
        exit;
    }
    $stmt->close();
} else {
    if ($categoria === '') {
        echo json_encode(['success' => false, 'message' => 'Nombre de categoría vacío']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id_categoria FROM categorias WHERE nombre_categoria = ?');
    $stmt->bind_param('s', $categoria);
    $stmt->execute();
    $stmt->bind_result($id_categoria);
    if ($stmt->fetch()) {
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare('INSERT INTO categorias (nombre_categoria, imagen_categoria) VALUES (?, ?)');
        $stmt->bind_param('ss', $categoria, $imagen_categoria);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error al insertar categoría']);
            exit;
        }
        $id_categoria = $stmt->insert_id;
        $stmt->close();
    }
}

$nombreCategoria = $categoria !== '' ? $categoria : obtenerNombreCategoria($conn, $id_categoria);

if (!empty($subcategorias)) {
    if (!empty($imagen_subcategoria)) {
        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imagen_subcategoria)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagen de subcategoría no válido']);
            exit;
        }
    }

    if (!empty($imagen_subcategoria)) {
        $stmt = $conn->prepare('INSERT INTO subcategorias (id_categoria, nombre_subcategoria, imagen_subcategoria) VALUES (?, ?, ?)');
    } else {
        $stmt = $conn->prepare('INSERT INTO subcategorias (id_categoria, nombre_subcategoria) VALUES (?, ?)');
    }

    foreach ($subcategorias as $subcat) {
        $subcat = trim($subcat);
        if ($subcat === '') continue;

        if (!empty($imagen_subcategoria)) {
            $stmt->bind_param('iss', $id_categoria, $subcat, $imagen_subcategoria);
        } else {
            $stmt->bind_param('is', $id_categoria, $subcat);
        }

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error al insertar subcategoría: ' . $subcat]);
            $stmt->close();
            exit;
        }

        $subcategoriasNuevas[] = [
            'id' => (int)$stmt->insert_id,
            'nombre' => $subcat
        ];
    }
    $stmt->close();
}

$todasSubcategorias = obtenerSubcategoriasDeCategoria($conn, $id_categoria);

echo json_encode([
    'success' => true,
    'id_categoria' => (int)$id_categoria,
    'nombre' => $nombreCategoria,
    'subcategorias' => $todasSubcategorias,
    'subcategorias_nuevas' => $subcategoriasNuevas
]);
$conn->close();
