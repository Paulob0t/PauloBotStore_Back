<?php
require_once "./admin/dist/db_config_dual.php";

// Sin caché para evitar datos obsoletos durante operación local
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$categoria_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;
$subcategoria_id = isset($_GET['subcategoria']) ? intval($_GET['subcategoria']) : null;
$sin_subcategoria = isset($_GET['sin_subcategoria']) ? true : false;

// Consulta optimizada con índice compuesto (activo, categoria, subcategoria)
$sql = "SELECT id_producto, id_categoria, id_subcategoria, nombre_producto, precio, descuento, imagen_principal 
    FROM productos 
    WHERE activo = 1";
$params = [];
$types = "";

if ($subcategoria_id) {
    $sql .= " AND id_subcategoria = ?";
    $params[] = $subcategoria_id;
    $types .= "i";
} elseif ($categoria_id) {
    $sql .= " AND id_categoria = ?";
    $params[] = $categoria_id;
    $types .= "i";
    
    if ($sin_subcategoria) {
        $sql .= " AND (id_subcategoria IS NULL OR id_subcategoria = 0)";
    }
}

$sql .= " ORDER BY nombre_producto ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$productos = [];

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $productos[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($productos);
