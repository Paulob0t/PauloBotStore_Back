<?php
require './admin/dist/db_config_dual.php';

echo "============================================\n";
echo "🔍 DIAGNÓSTICO DE CONEXIÓN A BD\n";
echo "============================================\n\n";

echo "📍 Ambiente detectado: " . (defined('USING_DB') ? USING_DB : 'NO DEFINIDO') . "\n";
echo "📍 Base de datos: " . (isset($db_name) ? $db_name : 'NO DEFINIDA') . "\n";
echo "📍 Conexión activa: " . ($conn ? 'SÍ' : 'NO') . "\n\n";

if ($conn) {
    echo "============================================\n";
    echo "📊 VERIFICANDO PRODUCTOS EN BD\n";
    echo "============================================\n\n";
    
    // Total de productos
    $result = mysqli_query($conn, 'SELECT COUNT(*) as total FROM productos');
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "✅ Total de productos: " . $row['total'] . "\n";
    } else {
        echo "❌ Error al consultar productos: " . mysqli_error($conn) . "\n";
    }
    
    // Productos destacados
    $result = mysqli_query($conn, 'SELECT COUNT(*) as total FROM productos WHERE destacado=1');
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "⭐ Productos destacados: " . $row['total'] . "\n";
    } else {
        echo "❌ Error al consultar destacados: " . mysqli_error($conn) . "\n";
    }
    
    // Productos destacados con orden
    $result = mysqli_query($conn, 'SELECT COUNT(*) as total FROM productos WHERE destacado=1 AND orden_destacado IS NOT NULL');
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "📌 Destacados con orden asignado: " . $row['total'] . "\n";
    }
    
    echo "\n";
    echo "============================================\n";
    echo "🔍 MUESTRA DE PRODUCTOS DESTACADOS\n";
    echo "============================================\n\n";
    
    $result = mysqli_query($conn, 'SELECT id_producto, nombre_producto, destacado, orden_destacado FROM productos WHERE destacado=1 LIMIT 5');
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: {$row['id_producto']} | {$row['nombre_producto']} | Orden: " . ($row['orden_destacado'] ?? 'NULL') . "\n";
        }
    } else {
        echo "❌ No se encontraron productos destacados\n";
    }
    
} else {
    echo "❌ NO HAY CONEXIÓN A BD DISPONIBLE\n";
}

echo "\n";
echo "============================================\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo "============================================\n";
