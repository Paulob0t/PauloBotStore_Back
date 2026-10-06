<?php
require_once "./admin/dist/db_config_dual.php";

// Sin caché para evitar datos obsoletos durante operación local
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Consulta optimizada con índices
$sql = "SELECT c.id_categoria, c.nombre_categoria, c.imagen_categoria, s.id_subcategoria, s.nombre_subcategoria, s.imagen_subcategoria 
        FROM categorias c 
        LEFT JOIN subcategorias s ON s.id_categoria = c.id_categoria 
        ORDER BY c.nombre_categoria, s.nombre_subcategoria";
$resultado = mysqli_query($conn, $sql);
$categorias = [];

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $id_categoria = $row['id_categoria'];
        if (!isset($categorias[$id_categoria])) {
            $categorias[$id_categoria] = [
                'id_categoria' => $id_categoria,
                'nombre_categoria' => $row['nombre_categoria'],
                'imagen_categoria' => $row['imagen_categoria'],
                'subcategorias' => []
            ];
        }
        if (!empty($row['id_subcategoria'])) {
            $categorias[$id_categoria]['subcategorias'][] = [
                'id_subcategoria' => $row['id_subcategoria'],
                'nombre_subcategoria' => $row['nombre_subcategoria'],
                'imagen_subcategoria' => $row['imagen_subcategoria']
            ];
        }
    }
}

$categorias = array_values($categorias); 
header('Content-Type: application/json');
echo json_encode($categorias, JSON_UNESCAPED_UNICODE);
