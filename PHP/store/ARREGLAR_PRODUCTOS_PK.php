<?php
/**
 * ARREGLAR_PRODUCTOS_PK.php
 * 
 * Arregla la tabla productos agregando PRIMARY KEY y AUTO_INCREMENT
 * También elimina duplicados si existen
 */

include "admin/dist/db_config_dual.php";

echo "═══════════════════════════════════════════════════════\n";
echo "  ARREGLAR TABLA PRODUCTOS - PRIMARY KEY\n";
echo "═══════════════════════════════════════════════════════\n\n";

// 1. Verificar estado actual
echo "1. Verificando estructura actual...\n";
$result = $conn->query("SHOW KEYS FROM productos WHERE Key_name = 'PRIMARY'");
if ($result->num_rows > 0) {
    echo "   ✅ Ya tiene PRIMARY KEY\n\n";
} else {
    echo "   ❌ NO tiene PRIMARY KEY\n\n";
    
    // 2. Eliminar productos con ID 0 o duplicados
    echo "2. Verificando productos con id_producto = 0...\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE id_producto = 0");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        echo "   ⚠️  Encontrados {$row['total']} productos con ID = 0\n";
        echo "   🗑️  Eliminando productos con ID = 0...\n";
        $conn->query("DELETE FROM productos WHERE id_producto = 0");
        echo "   ✅ Eliminados\n\n";
    } else {
        echo "   ✅ No hay productos con ID = 0\n\n";
    }
    
    // 3. Buscar otros duplicados
    echo "3. Buscando duplicados en id_producto...\n";
    $result = $conn->query("
        SELECT id_producto, COUNT(*) as cantidad 
        FROM productos 
        GROUP BY id_producto 
        HAVING cantidad > 1
    ");
    
    if ($result->num_rows > 0) {
        echo "   ⚠️  Encontrados " . $result->num_rows . " IDs duplicados:\n";
        while ($row = $result->fetch_assoc()) {
            echo "      ID {$row['id_producto']}: {$row['cantidad']} registros\n";
        }
        
        // Eliminar duplicados (mantener el más reciente)
        echo "\n   Eliminando duplicados (manteniendo el más reciente)...\n";
        $conn->query("
            DELETE p1 FROM productos p1
            INNER JOIN productos p2 
            WHERE p1.id_producto = p2.id_producto 
            AND p1.fecha_creacion < p2.fecha_creacion
        ");
        echo "   ✅ Duplicados eliminados\n\n";
    } else {
        echo "   ✅ No hay duplicados\n\n";
    }
    
    // 3. Verificar que no haya NULLs
    echo "4. Verificando valores NULL en id_producto...\n";
    $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE id_producto IS NULL");
    $row = $result->fetch_assoc();
    if ($row['total'] > 0) {
        echo "   ⚠️  Hay {$row['total']} registros con id_producto NULL\n";
        echo "   ❌ NO se puede crear PRIMARY KEY hasta eliminarlos\n\n";
        exit(1);
    } else {
        echo "   ✅ No hay NULLs\n\n";
    }
    
    // 4. Obtener el MAX id_producto
    echo "5. Obteniendo último ID...\n";
    $result = $conn->query("SELECT COALESCE(MAX(id_producto), 0) as max_id FROM productos");
    $row = $result->fetch_assoc();
    $maxId = $row['max_id'];
    echo "   Último ID: $maxId\n\n";
    
    // 5. Agregar PRIMARY KEY y AUTO_INCREMENT
    echo "6. Agregando PRIMARY KEY y AUTO_INCREMENT...\n";
    $conn->query("ALTER TABLE productos MODIFY id_producto INT(11) NOT NULL");
    $conn->query("ALTER TABLE productos ADD PRIMARY KEY (id_producto)");
    $conn->query("ALTER TABLE productos MODIFY id_producto INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=" . ($maxId + 1));
    
    if ($conn->error) {
        echo "   ❌ Error: " . $conn->error . "\n\n";
    } else {
        echo "   ✅ PRIMARY KEY agregada\n";
        echo "   ✅ AUTO_INCREMENT configurado (siguiente: " . ($maxId + 1) . ")\n\n";
    }
}

// 6. Verificar resultado final
echo "7. Verificación final...\n";
$result = $conn->query("DESCRIBE productos");
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] == 'id_producto') {
        echo "   Campo: {$row['Field']}\n";
        echo "   Tipo: {$row['Type']}\n";
        echo "   Key: {$row['Key']}\n";
        echo "   Extra: {$row['Extra']}\n";
        
        if ($row['Key'] == 'PRI' && strpos($row['Extra'], 'auto_increment') !== false) {
            echo "\n   ✅ ✅ ✅ TODO CORRECTO ✅ ✅ ✅\n";
        }
    }
}

$conn->close();

echo "\n═══════════════════════════════════════════════════════\n";
echo "  COMPLETADO\n";
echo "═══════════════════════════════════════════════════════\n";
?>
