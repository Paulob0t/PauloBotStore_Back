<?php
/**
 * Test de conexión BD para NUBE
 * Sube este archivo a vendingbox.online/sistema/admin/dist/
 * y accede a: https://vendingbox.online/sistema/admin/dist/test_db_nube.php
 */

// Mostrar TODOS los errores (para diagnóstico)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 TEST DE CONEXIÓN BD NUBE</h1>";
echo "<hr>";

echo "<h2>📋 Información del servidor:</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>PHP OS:</strong> " . PHP_OS . "</li>";
echo "<li><strong>Server Name:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'N/A') . "</li>";
echo "<li><strong>HTTP Host:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🔌 Test 1: Conexión directa a BD</h2>";

try {
    $conn = new mysqli(
        'localhost',
        'colegos_vending',
        'IfbUK2ClF~bV',
        'colegos_vending'
    );
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ <strong>ERROR de conexión:</strong> " . $conn->connect_error . "</p>";
        echo "<p><strong>Código de error:</strong> " . $conn->connect_errno . "</p>";
    } else {
        echo "<p style='color: green;'>✅ <strong>Conexión exitosa!</strong></p>";
        echo "<p><strong>Host info:</strong> " . $conn->host_info . "</p>";
        echo "<p><strong>Server info:</strong> " . $conn->server_info . "</p>";
        echo "<p><strong>Character set:</strong> " . $conn->character_set_name() . "</p>";
        
        // Test query
        $result = $conn->query("SELECT DATABASE() as db_name");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p><strong>Base de datos activa:</strong> " . $row['db_name'] . "</p>";
            $result->free();
        }
        
        // Listar tablas
        echo "<h3>📊 Tablas disponibles:</h3>";
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
            $result->free();
        }
        
        // Verificar tabla productos
        echo "<h3>🔍 Verificar tabla productos:</h3>";
        $result = $conn->query("SELECT COUNT(*) as total FROM productos");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ Tabla productos existe - Total registros: <strong>" . $row['total'] . "</strong></p>";
            $result->free();
        } else {
            echo "<p style='color: red;'>❌ Error al consultar tabla productos: " . $conn->error . "</p>";
        }
        
        // Verificar tabla sincronizacion_log
        echo "<h3>🔍 Verificar tabla sincronizacion_log:</h3>";
        $result = $conn->query("SELECT COUNT(*) as total FROM sincronizacion_log");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ Tabla sincronizacion_log existe - Total registros: <strong>" . $row['total'] . "</strong></p>";
            $result->free();
        } else {
            echo "<p style='color: red;'>❌ Error al consultar tabla sincronizacion_log: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Excepción capturada:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔌 Test 2: Cargar db_config_dual.php</h2>";

try {
    if (file_exists('db_config_dual.php')) {
        echo "<p style='color: green;'>✅ Archivo db_config_dual.php encontrado</p>";
        
        ob_start();
        require_once 'db_config_dual.php';
        $output = ob_get_clean();
        
        if (!empty($output)) {
            echo "<p style='color: orange;'>⚠️ El archivo produjo output inesperado:</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px;'>" . htmlspecialchars($output) . "</pre>";
        }
        
        if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
            echo "<p style='color: green;'>✅ Variable \$conn existe y está conectada</p>";
            echo "<p><strong>USING_DB:</strong> " . (defined('USING_DB') ? USING_DB : 'NO DEFINIDO') . "</p>";
            echo "<p><strong>IS_LOCAL:</strong> " . (defined('IS_LOCAL') ? (IS_LOCAL ? 'true' : 'false') : 'NO DEFINIDO') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Variable \$conn no está disponible o tiene error</p>";
            if (isset($conn) && $conn->connect_error) {
                echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>❌ Archivo db_config_dual.php NO encontrado</p>";
        echo "<p><strong>Path esperado:</strong> " . __DIR__ . "/db_config_dual.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Error al cargar db_config_dual.php:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🔌 Test 3: Simular guardado de producto</h2>";

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    try {
        // Datos de prueba
        $datos_test = [
            'id_usuario' => 1,
            'id_categoria' => 1,
            'id_subcategoria' => null,
            'nombre_producto' => 'TEST - Producto de Prueba',
            'descripcion' => 'Este es un producto de prueba del diagnóstico',
            'precio' => 10.00,
            'descuento' => null,
            'stock' => 5,
            'sku' => 'TEST-001',
            'ubicacion' => 'A1',
            'imagen_principal' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'imagen_secundaria_1' => null,
            'imagen_secundaria_2' => null,
            'imagen_secundaria_3' => null,
            'destacado' => 0,
            'orden_destacado' => null,
            'activo' => 1
        ];
        
        echo "<p>Intentando preparar query INSERT...</p>";
        
        $stmt = $conn->prepare("INSERT INTO productos (
            id_usuario, id_categoria, id_subcategoria, nombre_producto, descripcion,
            precio, descuento, stock, sku, ubicacion, imagen_principal, imagen_secundaria_1,
            imagen_secundaria_2, imagen_secundaria_3, destacado, orden_destacado, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            echo "<p style='color: red;'>❌ Error al preparar statement: " . $conn->error . "</p>";
        } else {
            echo "<p style='color: green;'>✅ Statement preparado correctamente (NO se ejecutará, solo test)</p>";
            $stmt->close();
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en test de INSERT: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No hay conexión disponible para este test</p>";
}

echo "<hr>";
echo "<p><strong>Fecha/Hora del test:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
