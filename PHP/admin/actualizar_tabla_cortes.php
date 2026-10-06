<?php
/**
 * Script para actualizar la tabla cortes
 * Agrega campo para almacenar IDs de comandas
 */

require_once 'db_config_dual.php';

echo "<h2>🔧 Actualizando estructura de tabla cortes</h2>";

// 1. Verificar si existe la columna comandas_ids
$check = $conn->query("SHOW COLUMNS FROM cortes LIKE 'comandas_ids'");

if ($check->num_rows == 0) {
    echo "<p>Agregando columna 'comandas_ids'...</p>";
    
    $sql = "ALTER TABLE `cortes` 
            ADD COLUMN `comandas_ids` TEXT DEFAULT NULL COMMENT 'IDs de comandas separados por comas' 
            AFTER `movimientos_json`";
    
    if ($conn->query($sql)) {
        echo "<p>✅ Columna 'comandas_ids' agregada correctamente</p>";
    } else {
        echo "<p>❌ Error: " . $conn->error . "</p>";
    }
} else {
    echo "<p>✅ La columna 'comandas_ids' ya existe</p>";
}

// 2. Verificar estructura final
echo "<h3>Estructura actual de la tabla cortes:</h3>";
$result = $conn->query("DESCRIBE cortes");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='cortes_caja.php'>← Volver a Cortes de Caja</a></p>";
?>
