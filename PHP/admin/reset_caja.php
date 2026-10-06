<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    die("❌ Debes iniciar sesión primero");
}

include_once 'db_config_dual.php';

echo "<h2>🔧 RESETEAR CAJA TRABADA</h2>";
echo "<p>Este script resetea la caja si está trabada y no te deja abrir ni cerrar</p>";

// Verificar si hay confirmación
if (!isset($_GET['confirmar'])) {
    echo "<div style='background: #fff3cd; padding: 20px; border: 1px solid #ffc107; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ ADVERTENCIA</h3>";
    echo "<p>Este script hará lo siguiente:</p>";
    echo "<ul>";
    echo "<li>Cerrará cualquier caja activa</li>";
    echo "<li>Resetará la configuración</li>";
    echo "<li>Te permitirá abrir una nueva caja</li>";
    echo "</ul>";
    echo "<p><strong>¿Estás seguro?</strong></p>";
    echo "<a href='?confirmar=si' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>SÍ, RESETEAR AHORA</a> ";
    echo "<a href='cortes_caja.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Cancelar</a>";
    echo "</div>";
    exit;
}

// RESETEAR
echo "<h3>Ejecutando reset...</h3>";

try {
    // 1. Cerrar cualquier caja activa
    $sql = "UPDATE config_caja SET caja_activa = 0, id_corte_actual = NULL WHERE id = 1";
    if ($conn->query($sql)) {
        echo "✅ Caja cerrada<br>";
    } else {
        echo "⚠️ Error al cerrar caja: " . $conn->error . "<br>";
    }
    
    // 2. Verificar estado
    $result = $conn->query("SELECT * FROM config_caja WHERE id = 1");
    if ($result && $result->num_rows > 0) {
        $config = $result->fetch_assoc();
        echo "✅ Estado de configuración:<br>";
        echo "<pre>";
        print_r($config);
        echo "</pre>";
    } else {
        // Crear configuración si no existe
        echo "⚠️ No existe configuración, creándola...<br>";
        $sql = "INSERT INTO config_caja (id, caja_activa, corte_automatico_habilitado, hora_corte_automatico, monto_inicial_default) 
                VALUES (1, 0, 1, '23:59:00', 0.00)";
        if ($conn->query($sql)) {
            echo "✅ Configuración creada<br>";
        } else {
            echo "❌ Error al crear configuración: " . $conn->error . "<br>";
        }
    }
    
    echo "<div style='background: #d4edda; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>✅ RESET COMPLETADO</h3>";
    echo "<p>La caja ha sido reseteada correctamente</p>";
    echo "<a href='cortes_caja.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sistema de Cortes</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3 style='color: #721c24;'>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn->close();
?>
