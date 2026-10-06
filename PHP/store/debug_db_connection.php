<?php
/**
 * 🔍 DIAGNÓSTICO COMPLETO DE BASE DE DATOS
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico BD - VendingBox</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f0f0f0; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #007bff; overflow-x: auto; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        .command { background: #2d2d2d; color: #fff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Completo de Base de Datos</h1>

    <div class="section">
        <h2>1. Información del Sistema</h2>
        <table>
            <tr>
                <td>Sistema Operativo</td>
                <td><strong><?php echo PHP_OS; ?></strong></td>
            </tr>
            <tr>
                <td>PHP Version</td>
                <td><strong><?php echo PHP_VERSION; ?></strong></td>
            </tr>
            <tr>
                <td>Ambiente Detectado</td>
                <td><strong><?php echo strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'WINDOWS (Laragon/XAMPP)' : 'LINUX'; ?></strong></td>
            </tr>
            <tr>
                <td>Ruta Actual</td>
                <td><code><?php echo __DIR__; ?></code></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>2. Extensión MySQLi</h2>
        <?php if (extension_loaded('mysqli')): ?>
            <p class="ok">✅ Extensión mysqli está instalada</p>
            <p>Cliente MySQL: <strong><?php echo mysqli_get_client_info(); ?></strong></p>
        <?php else: ?>
            <p class="error">❌ Extensión mysqli NO está instalada</p>
            <p>Necesitas habilitar la extensión mysqli en php.ini</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>3. Conexión a MySQL (sin base de datos específica)</h2>
        <?php
        $test_conn = @new mysqli('localhost', 'root', '');
        
        if ($test_conn->connect_error): ?>
            <p class="error">❌ No se puede conectar al servidor MySQL</p>
            <p><strong>Error:</strong> <?php echo $test_conn->connect_error; ?></p>
            <p><strong>Código de error:</strong> <?php echo $test_conn->connect_errno; ?></p>
            
            <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 15px;">
                <h3>🔧 Soluciones:</h3>
                <ol>
                    <li><strong>Verifica que MySQL esté corriendo:</strong>
                        <div class="command">
                            # Abre Laragon y verifica que MySQL esté iniciado (botón verde)
                        </div>
                    </li>
                    <li><strong>O inicia MySQL desde terminal:</strong>
                        <div class="command">
                            mysql --version
                        </div>
                    </li>
                    <li><strong>Verifica el puerto (por defecto 3306):</strong>
                        <div class="command">
                            netstat -ano | findstr :3306
                        </div>
                    </li>
                </ol>
            </div>
        <?php else: ?>
            <p class="ok">✅ Conexión exitosa al servidor MySQL</p>
            <p>Host info: <strong><?php echo $test_conn->host_info; ?></strong></p>
            <p>Versión del servidor: <strong><?php echo $test_conn->server_info; ?></strong></p>
            
            <!-- Listar todas las bases de datos disponibles -->
            <h3>Bases de datos disponibles:</h3>
            <?php
            $result = $test_conn->query("SHOW DATABASES");
            if ($result):
                $databases = [];
                while ($row = $result->fetch_row()) {
                    $databases[] = $row[0];
                }
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre de Base de Datos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($databases as $index => $db): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><code><?php echo htmlspecialchars($db); ?></code>
                                <?php if ($db === 'vending'): ?>
                                    <span class="ok">← Correcta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (!in_array('vending', $databases)): ?>
                    <p class="error">❌ La base de datos <code>vending</code> NO existe</p>
                <?php else: ?>
                    <p class="ok">✅ La base de datos <code>vending</code> existe</p>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if (isset($test_conn) && !$test_conn->connect_error): ?>
    <div class="section">
        <h2>4. Prueba de Conexión a BD 'colegos_vending'</h2>
        <?php
        $vending_conn = @new mysqli('localhost', 'root', '', 'colegos_vending');
        
        if ($vending_conn->connect_error): ?>
            <p class="error">❌ No se puede conectar a la base de datos 'colegos_vending'</p>
            <p><strong>Error:</strong> <?php echo $vending_conn->connect_error; ?></p>
            <p><strong>Código:</strong> <?php echo $vending_conn->connect_errno; ?></p>
            
            <?php if ($vending_conn->connect_errno === 1049): ?>
                <div style="background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin-top: 15px;">
                    <h3>❌ Base de datos 'vending' no existe</h3>
                    <p><strong>Solución:</strong></p>
                    <ol>
                        <li>Abre phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                        <li>Crea una nueva base de datos llamada <code>vending</code></li>
                        <li>Importa el archivo <code>vending.sql</code> que está en: <br>
                            <code><?php echo dirname(dirname(__DIR__)); ?>\vending.sql</code>
                        </li>
                    </ol>
                    
                    <h4>O créala desde terminal:</h4>
                    <div class="command">
                        mysql -u root -e "CREATE DATABASE vending CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"<br>
                        mysql -u root vending < "<?php echo dirname(dirname(__DIR__)); ?>\vending.sql"
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p class="ok">✅ Conexión exitosa a la base de datos 'vending'</p>
            <p>Charset: <strong><?php echo $vending_conn->character_set_name(); ?></strong></p>
            
            <!-- Verificar tablas -->
            <h3>Tablas en la base de datos:</h3>
            <?php
            $tables_result = $vending_conn->query("SHOW TABLES");
            if ($tables_result && $tables_result->num_rows > 0):
                $tables = [];
                while ($row = $tables_result->fetch_row()) {
                    $tables[] = $row[0];
                }
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tabla</th>
                            <th>Registros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $index => $table): 
                            $count_result = $vending_conn->query("SELECT COUNT(*) as total FROM `$table`");
                            $count = $count_result ? $count_result->fetch_assoc()['total'] : 'Error';
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><code><?php echo htmlspecialchars($table); ?></code></td>
                            <td><?php echo $count; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p class="ok">✅ Base de datos configurada correctamente con <?php echo count($tables); ?> tablas</p>
                
                <!-- Verificar tabla productos -->
                <?php if (in_array('productos', $tables)): ?>
                    <h3>Tabla 'productos':</h3>
                    <?php
                    $productos_count = $vending_conn->query("SELECT COUNT(*) as total FROM productos");
                    $total_productos = $productos_count ? $productos_count->fetch_assoc()['total'] : 0;
                    
                    $destacados_count = $vending_conn->query("SELECT COUNT(*) as total FROM productos WHERE destacado = 1");
                    $total_destacados = $destacados_count ? $destacados_count->fetch_assoc()['total'] : 0;
                    ?>
                    <p>Total de productos: <strong><?php echo $total_productos; ?></strong></p>
                    <p>Productos destacados: <strong><?php echo $total_destacados; ?></strong></p>
                <?php endif; ?>
                
            <?php else: ?>
                <p class="warning">⚠️ La base de datos existe pero no tiene tablas</p>
                <p>Necesitas importar el archivo <code>vending.sql</code></p>
            <?php endif; ?>
            
            <?php $vending_conn->close(); ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>5. Archivo de Configuración</h2>
        <?php
        $config_file = __DIR__ . '/../admin/dist/db_config_dual.php';
        ?>
        <p>Ubicación: <code><?php echo $config_file; ?></code></p>
        <?php if (file_exists($config_file)): ?>
            <p class="ok">✅ Archivo de configuración existe</p>
            <p>El archivo está configurado para conectarse a:</p>
            <ul>
                <li>Host: <code>localhost</code></li>
                <li>Usuario: <code>root</code></li>
                <li>Password: <code>(vacío)</code></li>
                <li>Base de datos: <code>vending</code></li>
            </ul>
        <?php else: ?>
            <p class="error">❌ Archivo de configuración no encontrado</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>6. Resumen y Próximos Pasos</h2>
        <?php
        $mysql_ok = isset($test_conn) && !$test_conn->connect_error;
        $vending_ok = isset($vending_conn) && !$vending_conn->connect_error;
        
        if ($mysql_ok && $vending_ok): ?>
            <p class="ok">✅ TODO ESTÁ FUNCIONANDO CORRECTAMENTE</p>
            <p>Puedes continuar usando el sistema. Si sigues viendo errores, limpia la caché del navegador.</p>
        <?php elseif ($mysql_ok): ?>
            <p class="warning">⚠️ MySQL está corriendo pero falta la base de datos 'vending'</p>
            <p><strong>Acción requerida:</strong></p>
            <ol>
                <li>Crea la base de datos <code>vending</code></li>
                <li>Importa el archivo <code>vending.sql</code></li>
            </ol>
        <?php else: ?>
            <p class="error">❌ MySQL no está corriendo o no está accesible</p>
            <p><strong>Acción requerida:</strong></p>
            <ol>
                <li>Abre Laragon</li>
                <li>Inicia MySQL (debe aparecer un botón verde)</li>
                <li>Recarga esta página</li>
            </ol>
        <?php endif; ?>
    </div>

    <?php if (isset($test_conn) && !$test_conn->connect_error) $test_conn->close(); ?>

    <hr>
    <p><small>Generado: <?php echo date('Y-m-d H:i:s'); ?> | Ruta: <?php echo __FILE__; ?></small></p>
</body>
</html>
