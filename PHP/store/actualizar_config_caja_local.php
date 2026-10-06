<?php
/**
 * 🔧 ACTUALIZAR BD LOCAL - Agregar columnas de auto-apertura
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/dist/db_config_dual.php';

echo "<h2>🔧 Actualizar BD Local - config_caja</h2>";

try {
    // Verificar si las columnas ya existen
    $check_auto = $conn->query("SHOW COLUMNS FROM config_caja LIKE 'auto_apertura_caja'");
    $check_horas = $conn->query("SHOW COLUMNS FROM config_caja LIKE 'horas_para_cierre'");
    
    $cambios = 0;
    
    // Agregar columna auto_apertura_caja si no existe
    if ($check_auto->num_rows == 0) {
        $sql1 = "ALTER TABLE `config_caja` 
                 ADD COLUMN `auto_apertura_caja` TINYINT(1) DEFAULT 1 
                 COMMENT 'Activar apertura automática después del cierre' 
                 AFTER `updated_at`";
        
        if ($conn->query($sql1)) {
            echo "<p style='color: green;'>✅ Columna 'auto_apertura_caja' agregada</p>";
            $cambios++;
        } else {
            echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Columna 'auto_apertura_caja' ya existe</p>";
    }
    
    // Agregar columna horas_para_cierre si no existe
    if ($check_horas->num_rows == 0) {
        $sql2 = "ALTER TABLE `config_caja` 
                 ADD COLUMN `horas_para_cierre` INT DEFAULT 24 
                 COMMENT 'Horas después de apertura para cierre automático' 
                 AFTER `auto_apertura_caja`";
        
        if ($conn->query($sql2)) {
            echo "<p style='color: green;'>✅ Columna 'horas_para_cierre' agregada</p>";
            $cambios++;
        } else {
            echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Columna 'horas_para_cierre' ya existe</p>";
    }
    
    // Verificar tabla cortes
    echo "<hr><h3>Verificando tabla 'cortes'...</h3>";
    $check_cortes_col = $conn->query("SHOW COLUMNS FROM cortes LIKE 'hora_cierre_programada'");
    
    if ($check_cortes_col->num_rows == 0) {
        $sql3 = "ALTER TABLE `cortes` 
                 ADD COLUMN `hora_cierre_programada` DATETIME NULL DEFAULT NULL 
                 COMMENT 'Hora programada para cierre automático' 
                 AFTER `hora`";
        
        if ($conn->query($sql3)) {
            echo "<p style='color: green;'>✅ Columna 'hora_cierre_programada' agregada a tabla 'cortes'</p>";
            $cambios++;
        } else {
            echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Columna 'hora_cierre_programada' ya existe</p>";
    }
    
    // Mostrar estructura final
    echo "<hr><h3>📋 Estructura final de config_caja:</h3><ul>";
    $columns = $conn->query("SHOW COLUMNS FROM config_caja");
    while ($col = $columns->fetch_assoc()) {
        echo "<li><strong>{$col['Field']}</strong> - {$col['Type']} 
              " . ($col['Default'] ? "(Default: {$col['Default']})" : "") . "</li>";
    }
    echo "</ul>";
    
    if ($cambios > 0) {
        echo "<hr><p style='color: green; font-size: 18px;'>✅ Base de datos actualizada: $cambios cambios realizados</p>";
        echo "<p>Ahora ejecuta: <a href='sincronizar_config_nube.php'><strong>Sincronizar configuración desde la Nube</strong></a></p>";
    } else {
        echo "<hr><p style='color: blue; font-size: 18px;'>ℹ️ La base de datos ya estaba actualizada</p>";
        echo "<p>Puedes ejecutar: <a href='sincronizar_config_nube.php'><strong>Sincronizar configuración desde la Nube</strong></a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
