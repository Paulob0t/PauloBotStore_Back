<?php
include 'db_config_dual.php';

echo "================================\n";
echo "PRUEBA DE CONEXION A BD LOCAL\n";
echo "================================\n\n";

if (defined('USING_DB')) {
    echo "✅ Modo de conexion: " . USING_DB . "\n";
} else {
    echo "❌ No se pudo determinar el modo de conexion\n";
}

if (isset($conn) && $conn) {
    echo "✅ BD: Conectada\n";
    echo "✅ Base de datos: " . (isset($db_name) ? $db_name : 'N/A') . "\n";
    
    // Probar una consulta simple
    $result = $conn->query("SELECT DATABASE() as db");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "✅ BD activa: " . $row['db'] . "\n";
    }
    
    // Ver cuántas tablas hay
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "✅ Tablas encontradas: " . $result->num_rows . "\n";
    }
} else {
    echo "❌ BD: ERROR - No conectada\n";
}

echo "\n================================\n";
