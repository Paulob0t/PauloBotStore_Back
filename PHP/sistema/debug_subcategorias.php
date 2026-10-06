<?php
require_once "./admin/dist/db_config_dual.php";

echo "<h1>🔍 DEBUG: Subcategorías en BD</h1>";

// Consultar subcategorías para la categoría 34 (Bebidas)
$sql = "SELECT * FROM subcategorias WHERE id_categoria = 34 ORDER BY nombre_subcategoria";
$resultado = mysqli_query($conn, $sql);

echo "<h2>Subcategorías en la categoría 34 (Bebidas):</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Imagen</th><th>ID Categoría</th></tr>";

$count = 0;
$subcategorias_vistas = [];

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $count++;
        $id = $row['id_subcategoria'];
        $nombre = $row['nombre_subcategoria'];
        
        // Marcar duplicados
        $duplicado = '';
        if (isset($subcategorias_vistas[$nombre])) {
            $duplicado = ' style="background-color: #ffdddd;"';
        }
        $subcategorias_vistas[$nombre] = true;
        
        echo "<tr$duplicado>";
        echo "<td>{$row['id_subcategoria']}</td>";
        echo "<td>{$row['nombre_subcategoria']}</td>";
        echo "<td>" . (empty($row['imagen_subcategoria']) ? 'Sin imagen' : 'Con imagen') . "</td>";
        echo "<td>{$row['id_categoria']}</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "<p><strong>Total de registros:</strong> $count</p>";

// Verificar si hay duplicados en nombres
$sql_duplicados = "SELECT nombre_subcategoria, COUNT(*) as total 
                   FROM subcategorias 
                   WHERE id_categoria = 34 
                   GROUP BY nombre_subcategoria 
                   HAVING total > 1";
$resultado_dup = mysqli_query($conn, $sql_duplicados);

if ($resultado_dup && mysqli_num_rows($resultado_dup) > 0) {
    echo "<h2 style='color: red;'>⚠️ DUPLICADOS ENCONTRADOS:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Nombre Subcategoría</th><th>Cantidad de duplicados</th></tr>";
    while ($row = mysqli_fetch_assoc($resultado_dup)) {
        echo "<tr>";
        echo "<td>{$row['nombre_subcategoria']}</td>";
        echo "<td>{$row['total']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h2 style='color: green;'>✅ No hay duplicados en la base de datos</h2>";
}

// Mostrar el JOIN que usa get_categories.php
echo "<h2>JOIN completo (como en get_categories.php):</h2>";
$sql_join = "SELECT c.id_categoria, c.nombre_categoria, s.id_subcategoria, s.nombre_subcategoria 
             FROM categorias c 
             LEFT JOIN subcategorias s ON s.id_categoria = c.id_categoria 
             WHERE c.id_categoria = 34
             ORDER BY s.nombre_subcategoria";
$resultado_join = mysqli_query($conn, $sql_join);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>#</th><th>ID Subcategoría</th><th>Nombre Subcategoría</th></tr>";

$count_join = 0;
if ($resultado_join && mysqli_num_rows($resultado_join) > 0) {
    while ($row = mysqli_fetch_assoc($resultado_join)) {
        $count_join++;
        echo "<tr>";
        echo "<td>$count_join</td>";
        echo "<td>{$row['id_subcategoria']}</td>";
        echo "<td>{$row['nombre_subcategoria']}</td>";
        echo "</tr>";
    }
}
echo "</table>";
echo "<p><strong>Total de filas del JOIN:</strong> $count_join</p>";
?>
