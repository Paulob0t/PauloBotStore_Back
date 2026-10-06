<?php
include "db_config_dual.php";

echo "<h2>🔍 DIAGNÓSTICO DE MEMORIA - PRODUCTOS</h2>";
echo "<hr>";

// 1. Contar productos
$result = $conn->query("SELECT COUNT(*) as total FROM productos");
$row = $result->fetch_assoc();
$total_productos = $row['total'];
echo "<h3>📊 Total de productos: <strong>$total_productos</strong></h3>";
$result->free();

// 2. Ver tamaño de campos TEXT/LONGTEXT
echo "<h3>📝 Tamaño de campos grandes:</h3>";
$result = $conn->query("
    SELECT 
        AVG(LENGTH(descripcion)) as avg_desc, 
        MAX(LENGTH(descripcion)) as max_desc,
        AVG(LENGTH(imagen_principal)) as avg_img1,
        MAX(LENGTH(imagen_principal)) as max_img1,
        AVG(LENGTH(imagen_secundaria_1)) as avg_img2,
        MAX(LENGTH(imagen_secundaria_1)) as max_img2
    FROM productos
");
$row = $result->fetch_assoc();
echo "<ul>";
echo "<li><strong>descripcion:</strong> Promedio: " . number_format($row['avg_desc']) . " bytes, Máximo: " . number_format($row['max_desc']) . " bytes</li>";
echo "<li><strong>imagen_principal:</strong> Promedio: " . number_format($row['avg_img1']) . " bytes, Máximo: " . number_format($row['max_img1']) . " bytes</li>";
echo "<li><strong>imagen_secundaria_1:</strong> Promedio: " . number_format($row['avg_img2']) . " bytes, Máximo: " . number_format($row['max_img2']) . " bytes</li>";
echo "</ul>";
$result->free();

// 3. Calcular memoria estimada si se cargan TODOS los productos CON imágenes
$total_bytes = $row['avg_desc'] + ($row['avg_img1'] * 4); // descripcion + 4 imágenes
$total_memoria_estimada = $total_bytes * $total_productos;
echo "<h3>💾 Memoria estimada si se cargan TODAS las imágenes:</h3>";
echo "<p style='color: red; font-size: 20px;'><strong>" . number_format($total_memoria_estimada / 1024 / 1024, 2) . " MB</strong></p>";

// 4. Calcular memoria SIN imágenes
$total_bytes_sin_img = $row['avg_desc'];
$total_memoria_sin_img = $total_bytes_sin_img * $total_productos;
echo "<h3>💾 Memoria estimada SIN imágenes (solo descripción):</h3>";
echo "<p style='color: green; font-size: 20px;'><strong>" . number_format($total_memoria_sin_img / 1024 / 1024, 2) . " MB</strong></p>";

// 5. Ver productos con descripciones muy grandes
echo "<h3>⚠️ Productos con descripciones >50KB:</h3>";
$result = $conn->query("
    SELECT id_producto, nombre_producto, LENGTH(descripcion) as desc_size
    FROM productos
    WHERE LENGTH(descripcion) > 50000
    ORDER BY desc_size DESC
    LIMIT 10
");
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Tamaño descripción</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_producto']}</td>";
        echo "<td>{$row['nombre_producto']}</td>";
        echo "<td>" . number_format($row['desc_size']) . " bytes</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>✅ Ninguno</p>";
}
$result->free();

// 6. Uso de memoria actual
echo "<h3>💻 Uso de memoria PHP actual:</h3>";
echo "<p><strong>" . number_format(memory_get_usage() / 1024 / 1024, 2) . " MB / " . ini_get('memory_limit') . "</strong></p>";

echo "<hr>";
echo "<h3>✅ CONCLUSIÓN:</h3>";
echo "<p>Si el error persiste, el problema podría ser:</p>";
echo "<ol>";
echo "<li>Hay muchos productos con imágenes cargándose</li>";
echo "<li>Algún producto tiene una descripción EXTREMADAMENTE grande</li>";
echo "<li>Hay un loop infinito acumulando memoria</li>";
echo "</ol>";

$conn->close();
?>
