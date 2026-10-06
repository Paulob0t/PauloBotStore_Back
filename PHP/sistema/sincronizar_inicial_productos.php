<?php
/**
 * sincronizar_inicial_productos.php
 * 
 * Script para hacer sincronización inicial de productos existentes
 * Inserta todos los productos de la BD local a sincronizacion_log
 * para que se envíen a la nube
 */

include "admin/dist/db_config_dual.php";

echo "=== SINCRONIZACION INICIAL DE PRODUCTOS ===\n\n";

// 1. Contar productos actuales
$result = $conn->query("SELECT COUNT(*) as total FROM productos");
$row = $result->fetch_assoc();
$totalProductos = $row['total'];

echo "Productos en BD local: $totalProductos\n";

// 2. Verificar cuántos ya están en sincronizacion_log
$result = $conn->query("SELECT COUNT(DISTINCT id_registro) as total FROM sincronizacion_log WHERE tabla = 'productos'");
$row = $result->fetch_assoc();
$yaRegistrados = $row['total'];

echo "Ya en sincronizacion_log: $yaRegistrados\n";
echo "Faltan por sincronizar: " . ($totalProductos - $yaRegistrados) . "\n\n";

// 3. Insertar productos faltantes en sincronizacion_log
$sql = "INSERT INTO sincronizacion_log (uuid, tabla, accion, id_registro, datos, origen, sincronizado, fecha_sync)
        SELECT 
            UUID() as uuid,
            'productos' as tabla,
            'INSERT' as accion,
            p.id_producto as id_registro,
            JSON_OBJECT(
                'id_producto', p.id_producto,
                'id_usuario', p.id_usuario,
                'id_categoria', p.id_categoria,
                'id_subcategoria', p.id_subcategoria,
                'nombre_producto', p.nombre_producto,
                'descripcion', p.descripcion,
                'precio', p.precio,
                'descuento', p.descuento,
                'stock', p.stock,
                'sku', p.sku,
                'ubicacion', p.ubicacion,
                'imagen_principal', p.imagen_principal,
                'imagen_secundaria_1', p.imagen_secundaria_1,
                'imagen_secundaria_2', p.imagen_secundaria_2,
                'imagen_secundaria_3', p.imagen_secundaria_3,
                'destacado', p.destacado,
                'orden_destacado', p.orden_destacado,
                'activo', p.activo,
                'fecha_creacion', p.fecha_creacion,
                'fecha_actualizacion', p.fecha_actualizacion
            ) as datos,
            'LOCAL_SYNC_INICIAL' as origen,
            0 as sincronizado,
            NOW() as fecha_sync
        FROM productos p
        LEFT JOIN sincronizacion_log sl ON sl.tabla = 'productos' AND sl.id_registro = p.id_producto
        WHERE sl.id_sync IS NULL";

if ($conn->query($sql)) {
    $insertados = $conn->affected_rows;
    echo "✅ Insertados en sincronizacion_log: $insertados productos\n";
    echo "\n✅ LISTO! En 1 minuto se sincronizarán a la nube automáticamente.\n";
    echo "\nPuedes verificar el progreso en:\n";
    echo "  C:\\xampp\\htdocs\\vendingbox.online\\sistema\\logs\\sync_nube_*.log\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

$conn->close();
?>
