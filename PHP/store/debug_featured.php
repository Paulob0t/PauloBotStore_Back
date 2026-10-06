<?php
/**
 * 🔍 DIAGNÓSTICO DE PRODUCTOS DESTACADOS
 */
require_once "./admin/dist/db_config_dual.php";

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico - Productos Destacados</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #007bff; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico de Productos Destacados</h1>

    <div class="section">
        <h2>1. Conexión a Base de Datos</h2>
        <?php if ($conn && !mysqli_connect_error()): ?>
            <p class="ok">✅ Conexión exitosa a la base de datos</p>
            <p>Base de datos: <strong><?php echo $db_name ?? 'N/A'; ?></strong></p>
        <?php else: ?>
            <p class="error">❌ Error de conexión: <?php echo mysqli_connect_error(); ?></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>2. Productos con destacado = 1</h2>
        <?php
        $sql = "SELECT id_producto, nombre_producto, destacado, orden_destacado 
                FROM productos 
                WHERE destacado = 1";
        $resultado = mysqli_query($conn, $sql);
        
        if ($resultado):
            $count = mysqli_num_rows($resultado);
            if ($count > 0): ?>
                <p class="ok">✅ Se encontraron <?php echo $count; ?> productos con destacado = 1</p>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Destacado</th>
                            <th>Orden Destacado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><?php echo $row['id_producto']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_producto']); ?></td>
                            <td><?php echo $row['destacado']; ?></td>
                            <td><?php echo $row['orden_destacado'] ?? '<span class="error">NULL</span>'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error">❌ No hay productos con destacado = 1</p>
                <p>💡 <strong>Solución:</strong> Ve al panel de administración y marca algunos productos como destacados.</p>
            <?php endif;
        else: ?>
            <p class="error">❌ Error en la consulta: <?php echo mysqli_error($conn); ?></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>3. Productos con destacado = 1 Y orden_destacado asignado</h2>
        <?php
        $sql = "SELECT id_producto, nombre_producto, destacado, orden_destacado, activo
                FROM productos 
                WHERE activo = 1 
                AND destacado = 1 
                AND orden_destacado IS NOT NULL
                ORDER BY orden_destacado ASC";
        $resultado = mysqli_query($conn, $sql);
        
        if ($resultado):
            $count = mysqli_num_rows($resultado);
            if ($count > 0): ?>
                <p class="ok">✅ Se encontraron <?php echo $count; ?> productos activos con destacado y orden_destacado</p>
                <p><strong>Estos son los que aparecerán en el carrusel:</strong></p>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Orden</th>
                            <th>Activo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><?php echo $row['id_producto']; ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_producto']); ?></td>
                            <td><?php echo $row['orden_destacado']; ?></td>
                            <td><?php echo $row['activo'] ? '✅' : '❌'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error">❌ No hay productos activos con destacado = 1 Y orden_destacado asignado</p>
                <p class="warning">⚠️ <strong>Este es probablemente tu problema</strong></p>
                <p>💡 <strong>Solución:</strong> Los productos destacados necesitan tener un valor en el campo <code>orden_destacado</code>.</p>
            <?php endif;
        else: ?>
            <p class="error">❌ Error en la consulta: <?php echo mysqli_error($conn); ?></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>4. Prueba del endpoint get_featured_products.php</h2>
        <?php
        $endpoint_url = 'http://' . $_SERVER['HTTP_HOST'] . '/sistema/get_featured_products.php';
        ?>
        <p>Endpoint: <a href="<?php echo $endpoint_url; ?>" target="_blank"><?php echo $endpoint_url; ?></a></p>
        <?php
        $json_response = @file_get_contents('get_featured_products.php');
        if ($json_response !== false):
            $data = json_decode($json_response, true);
            ?>
            <p class="ok">✅ El endpoint responde correctamente</p>
            <p>Productos devueltos: <strong><?php echo count($data ?? []); ?></strong></p>
            <pre><?php echo htmlspecialchars($json_response); ?></pre>
        <?php else: ?>
            <p class="error">❌ No se pudo leer el endpoint</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>5. Tracking Prevention (Storage)</h2>
        <p class="warning">⚠️ Los errores de "Tracking Prevention blocked access to storage" son del navegador.</p>
        <p><strong>Causas comunes:</strong></p>
        <ul>
            <li>Edge/Firefox en modo estricto de privacidad</li>
            <li>Extensiones de bloqueo de rastreadores</li>
            <li>Configuración de cookies bloqueadas</li>
        </ul>
        <p><strong>Soluciones:</strong></p>
        <ol>
            <li>En Edge: Configuración → Privacidad → Prevención de seguimiento → Cambiar a "Básico"</li>
            <li>Agregar el sitio a excepciones de prevención de rastreo</li>
            <li>Desactivar extensiones de privacidad temporalmente</li>
        </ol>
        <p>💡 <strong>Nota:</strong> Estos errores no afectan la funcionalidad, solo el caché local.</p>
    </div>

    <div class="section">
        <h2>6. Resumen del Diagnóstico</h2>
        <?php
        // Verificar productos destacados
        $sql_check = "SELECT COUNT(*) as total FROM productos WHERE activo = 1 AND destacado = 1 AND orden_destacado IS NOT NULL";
        $result_check = mysqli_query($conn, $sql_check);
        $row_check = mysqli_fetch_assoc($result_check);
        $total_featured = $row_check['total'];
        
        if ($total_featured > 0): ?>
            <p class="ok">✅ <strong>Todo está bien configurado</strong></p>
            <p>Tienes <?php echo $total_featured; ?> productos destacados que deberían aparecer en el carrusel.</p>
            <p>Si no aparecen en la página principal, verifica la consola del navegador.</p>
        <?php else: ?>
            <p class="error">❌ <strong>Problema identificado</strong></p>
            <p>No hay productos configurados correctamente como destacados.</p>
            <p><strong>Pasos para solucionarlo:</strong></p>
            <ol>
                <li>Ve al panel de administración de productos</li>
                <li>Selecciona productos que quieras destacar</li>
                <li>Marca la casilla "Destacado"</li>
                <li>Asigna un número en "Orden Destacado" (1, 2, 3, etc.)</li>
                <li>Asegúrate de que el producto esté "Activo"</li>
                <li>Guarda los cambios</li>
            </ol>
        <?php endif; ?>
    </div>

    <hr>
    <p><small>Generado: <?php echo date('Y-m-d H:i:s'); ?></small></p>
</body>
</html>
