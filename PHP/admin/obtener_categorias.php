<?php
require 'db_config_dual.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$categorias = [];
$res = $conn->query("SELECT c.id_categoria, c.nombre_categoria, s.id_subcategoria, s.nombre_subcategoria
                     FROM categorias c
                     LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
                     ORDER BY c.id_categoria, s.id_subcategoria");

while ($row = $res->fetch_assoc()) {
    $cat_id = $row['id_categoria'];
    if (!isset($categorias[$cat_id])) {
        $categorias[$cat_id] = [
            'id' => $cat_id,
            'nombre' => $row['nombre_categoria'],
            'subcategorias' => []
        ];
    }
    if ($row['id_subcategoria']) {
        $categorias[$cat_id]['subcategorias'][] = [
            'id' => $row['id_subcategoria'],
            'nombre' => $row['nombre_subcategoria']
        ];
    }
}
echo json_encode([
    'success' => true,
    'categorias' => array_values($categorias)
]);
