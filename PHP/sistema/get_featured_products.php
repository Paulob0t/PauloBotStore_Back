<?php
require_once "./admin/dist/db_config_dual.php";

// Caché de 5 minutos para productos destacados
// Sin caché para evitar datos obsoletos durante operación local
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

// Obtener productos destacados ordenados por su número de orden
// Solo productos con destacado = 1 Y que tengan orden_destacado asignado
$sql = "SELECT id_producto, id_categoria, id_subcategoria, nombre_producto, precio, descuento, imagen_principal, orden_destacado
    FROM productos 
    WHERE activo = 1 
    AND destacado = 1 
    AND orden_destacado IS NOT NULL
    ORDER BY orden_destacado ASC";

$resultado = mysqli_query($conn, $sql);
$productos = [];

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $productos[] = $row;
    }
}

// Log para depuración (puedes comentar esta línea en producción)
error_log("Productos destacados encontrados: " . count($productos));

echo json_encode($productos);
