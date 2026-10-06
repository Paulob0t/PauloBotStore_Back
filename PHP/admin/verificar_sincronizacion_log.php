<?php
/**
 * Verificar si tabla sincronizacion_log existe en NUBE
 * Sube a: vendingbox.online/sistema/admin/dist/verificar_sincronizacion_log.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 VERIFICAR TABLA sincronizacion_log EN NUBE</h1>";
echo "<hr>";

// Conectar a BD
try {
    $conn = new mysqli('localhost', 'colegos_vending', 'IfbUK2ClF~bV', 'colegos_vending');
    
    if ($conn->connect_error) {
        die("<p style='color: red;'>❌ Error de conexión: " . $conn->connect_error . "</p>");
    }
    
    echo "<p style='color: green;'>✅ Conectado a BD</p>";
    
    // 1. Verificar si tabla existe
    $result = $conn->query("SHOW TABLES LIKE 'sincronizacion_log'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Tabla 'sincronizacion_log' EXISTE</p>";
        $result->free();
        
        // 2. Ver estructura
        echo "<h3>📋 Estructura de la tabla:</h3>";
        $result = $conn->query("DESCRIBE sincronizacion_log");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $result->free();
        
        // 3. Contar registros
        $result = $conn->query("SELECT COUNT(*) as total FROM sincronizacion_log");
        $row = $result->fetch_assoc();
        echo "<p><strong>Total de registros:</strong> " . $row['total'] . "</p>";
        $result->free();
        
        // 4. Test de INSERT
        echo "<h3>🧪 Test de INSERT:</h3>";
        $test_stmt = $conn->prepare("INSERT INTO sincronizacion_log (uuid, tabla, accion, id_registro, datos, origen, sincronizado) VALUES (UUID(), 'test', 'TEST', 999, '{\"test\":true}', 'TEST', 0)");
        
        if (!$test_stmt) {
            echo "<p style='color: red;'>❌ Error al preparar: " . $conn->error . "</p>";
        } else {
            if ($test_stmt->execute()) {
                $insert_id = $conn->insert_id;
                echo "<p style='color: green;'>✅ INSERT exitoso! ID: " . $insert_id . "</p>";
                
                // Eliminar test
                $conn->query("DELETE FROM sincronizacion_log WHERE id_sync = $insert_id");
                echo "<p>🗑️ Registro de test eliminado</p>";
            } else {
                echo "<p style='color: red;'>❌ Error al ejecutar: " . $test_stmt->error . "</p>";
            }
            $test_stmt->close();
        }
        
    } else {
        echo "<p style='color: red;'>❌ Tabla 'sincronizacion_log' NO EXISTE</p>";
        echo "<h3>📊 Tablas disponibles:</h3>";
        $result = $conn->query("SHOW TABLES");
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        $result->free();
        
        echo "<hr>";
        echo "<h3>⚠️ NECESITAS CREAR LA TABLA</h3>";
        echo "<p>Copia este SQL en phpMyAdmin:</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>";
        echo "CREATE TABLE `sincronizacion_log` (
  `id_sync` INT(11) NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(36) DEFAULT NULL,
  `tabla` VARCHAR(50) NOT NULL,
  `accion` ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  `id_registro` INT(11) NOT NULL,
  `datos` TEXT,
  `fecha_sync` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `origen` VARCHAR(50) DEFAULT 'LOCAL',
  `hash_datos` VARCHAR(64) DEFAULT NULL,
  `sincronizado` TINYINT(1) DEFAULT 0,
  `fecha_sincronizado` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_sync`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_tabla_id` (`tabla`, `id_registro`),
  KEY `idx_sincronizado` (`sincronizado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        echo "</pre>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
