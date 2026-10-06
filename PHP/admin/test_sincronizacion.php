<?php
/**
 * 🔄 PANEL DE PRUEBA DE SINCRONIZACIÓN
 * Herramienta para diagnosticar y probar la sincronización entre BD local y nube
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_config_dual.php';

$accion = $_GET['accion'] ?? 'panel';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 Test Sincronización BD</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #FFD93D 0%, #FFA500 100%);
            color: #333;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .status-box {
            background: #f8f9fa;
            border-left: 5px solid #007bff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .status-box.success { border-color: #28a745; background: #d4edda; }
        .status-box.warning { border-color: #ffc107; background: #fff3cd; }
        .status-box.error { border-color: #dc3545; background: #f8d7da; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border-color: #FFD93D;
        }
        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .btn {
            background: #FFD93D;
            color: #333;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn:hover {
            background: #FFA500;
            transform: scale(1.05);
        }
        .btn-blue { background: #007bff; color: white; }
        .btn-blue:hover { background: #0056b3; }
        .btn-green { background: #28a745; color: white; }
        .btn-green:hover { background: #218838; }
        .btn-red { background: #dc3545; color: white; }
        .btn-red:hover { background: #c82333; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        table th {
            background: #FFD93D;
            color: #333;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #FFD93D;
            margin: 10px 0;
        }
        .loading {
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FFD93D;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #modalResultado {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        #modalResultado.show {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
    </style>
    <script>
        function ejecutarSync(accion) {
            // Mostrar loading
            const modal = document.getElementById('modalResultado');
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = '<div class="loading"><div class="spinner"></div><p>Ejecutando sincronización...</p></div>';
            modal.classList.add('show');
            
            // Ejecutar
            fetch('ejecutar_sync.php?accion=' + accion)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    
                    if (data.success) {
                        html = '<div class="status-box success">';
                        html += '<h3>✅ ' + data.mensaje + '</h3>';
                    } else {
                        html = '<div class="status-box error">';
                        html += '<h3>❌ ' + data.mensaje + '</h3>';
                    }
                    
                    if (data.datos) {
                        html += '<pre>' + JSON.stringify(data.datos, null, 2) + '</pre>';
                    }
                    
                    html += '</div>';
                    html += '<button onclick="cerrarModal()" class="btn">Cerrar</button>';
                    html += '<button onclick="location.reload()" class="btn btn-blue">Recargar Página</button>';
                    
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = '<div class="status-box error"><h3>❌ Error</h3><p>' + error + '</p></div>';
                    modalBody.innerHTML += '<button onclick="cerrarModal()" class="btn">Cerrar</button>';
                });
        }
        
        function cerrarModal() {
            document.getElementById('modalResultado').classList.remove('show');
        }
    </script>
</head>
<body>
    <!-- Modal de Resultado -->
    <div id="modalResultado">
        <div class="modal-content" id="modalBody">
            <div class="loading"><div class="spinner"></div></div>
        </div>
    </div>
    <div class="container">
        <div class="header">
            <h1>🔄 Panel de Sincronización BD</h1>
            <p>Diagnóstico y pruebas de sincronización Local ↔ Nube</p>
        </div>
        
        <div class="content">
            <?php
            // ====================================
            // PANEL PRINCIPAL
            // ====================================
            if ($accion === 'panel') {
                ?>
                <!-- Estado de Conexión -->
                <div class="status-box <?php echo isset($conn) && $conn ? 'success' : 'error'; ?>">
                    <h2>📡 Estado de Conexión</h2>
                    <?php if (isset($conn) && $conn): ?>
                        <p><strong>✅ Conectado a:</strong> <?php echo defined('USING_DB') ? USING_DB : 'Desconocido'; ?></p>
                        <p><strong>🌍 Ambiente:</strong> <?php echo IS_LOCAL ? 'Local (XAMPP)' : 'Nube (cPanel)'; ?></p>
                    <?php else: ?>
                        <p><strong>❌ Error de conexión a la base de datos</strong></p>
                    <?php endif; ?>
                </div>

                <!-- Botones de Acción -->
                <div class="grid">
                    <div class="card">
                        <h3>📊 Verificar Estado</h3>
                        <p>Revisa el estado actual de la sincronización y registros pendientes</p>
                        <a href="?accion=estado" class="btn btn-blue">Ver Estado</a>
                    </div>

                    <div class="card">
                        <h3>🔍 Registros Pendientes</h3>
                        <p>Muestra los registros que están esperando sincronizarse</p>
                        <a href="?accion=pendientes" class="btn btn-blue">Ver Pendientes</a>
                    </div>

                    <div class="card">
                        <h3>🧪 Crear Registro de Prueba</h3>
                        <p>Inserta un registro de prueba para verificar la sincronización</p>
                        <a href="?accion=crear_prueba" class="btn btn-green">Crear Prueba</a>
                    </div>

                    <div class="card">
                        <h3>🔄 Ver Datos de Tablas</h3>
                        <p>Compara los datos entre las tablas sincronizadas</p>
                        <a href="?accion=comparar" class="btn btn-blue">Comparar Datos</a>
                    </div>

                    <div class="card">
                        <h3>📝 Verificar Estructura</h3>
                        <p>Verifica que las tablas tengan las columnas necesarias</p>
                        <a href="?accion=estructura" class="btn btn-blue">Ver Estructura</a>
                    </div>

                    <div class="card">
                        <h3>🌐 Probar Endpoint Nube</h3>
                        <p>Verifica la conectividad con el servidor en la nube</p>
                        <a href="?accion=test_endpoint" class="btn btn-blue">Test Endpoint</a>
                    </div>

                    <div class="card" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                        <h3>🚀 Ejecutar Sincronización</h3>
                        <p>Sincroniza los cambios con la nube ahora mismo</p>
                        <button onclick="ejecutarSync('sync_nube')" class="btn btn-green">
                            ▲ Sincronizar a Nube
                        </button>
                        <button onclick="ejecutarSync('sync_local')" class="btn btn-blue">
                            ▼ Sincronizar desde Nube
                        </button>
                    </div>

                    <div class="card">
                        <h3>🧹 Mantenimiento</h3>
                        <p>Limpia registros ya sincronizados</p>
                        <button onclick="ejecutarSync('limpiar_sincronizados')" class="btn btn-red">
                            Limpiar Sincronizados
                        </button>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <a href="index.php" class="btn">« Volver al Panel</a>
                </div>
                <?php
            }
            
            // ====================================
            // VER ESTADO
            // ====================================
            elseif ($accion === 'estado') {
                ?>
                <h2>📊 Estado de Sincronización</h2>
                
                <?php
                // Verificar si existe la tabla sincronizacion_log
                $tabla_existe = false;
                $check = $conn->query("SHOW TABLES LIKE 'sincronizacion_log'");
                if ($check && $check->num_rows > 0) {
                    $tabla_existe = true;
                    
                    // Estadísticas
                    $stats = [];
                    $stats['total'] = $conn->query("SELECT COUNT(*) as c FROM sincronizacion_log")->fetch_assoc()['c'];
                    $stats['pendientes'] = $conn->query("SELECT COUNT(*) as c FROM sincronizacion_log WHERE sincronizado = 0")->fetch_assoc()['c'];
                    $stats['sincronizados'] = $conn->query("SELECT COUNT(*) as c FROM sincronizacion_log WHERE sincronizado = 1")->fetch_assoc()['c'];
                    
                    ?>
                    <div class="grid">
                        <div class="card">
                            <h3>Total de Registros</h3>
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="card">
                            <h3>⏳ Pendientes</h3>
                            <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pendientes']; ?></div>
                        </div>
                        <div class="card">
                            <h3>✅ Sincronizados</h3>
                            <div class="stat-number" style="color: #28a745;"><?php echo $stats['sincronizados']; ?></div>
                        </div>
                    </div>

                    <h3>Últimos 10 registros de sincronización:</h3>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tabla</th>
                            <th>Acción</th>
                            <th>ID Registro</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                        <?php
                        $result = $conn->query("SELECT * FROM sincronizacion_log ORDER BY id_sync DESC LIMIT 10");
                        while ($row = $result->fetch_assoc()) {
                            $badge_class = $row['sincronizado'] == 1 ? 'badge-success' : 'badge-warning';
                            $estado_text = $row['sincronizado'] == 1 ? 'Sincronizado' : 'Pendiente';
                            echo "<tr>";
                            echo "<td>{$row['id_sync']}</td>";
                            echo "<td><strong>{$row['tabla']}</strong></td>";
                            echo "<td>{$row['accion']}</td>";
                            echo "<td>{$row['id_registro']}</td>";
                            echo "<td><span class='badge {$badge_class}'>{$estado_text}</span></td>";
                            echo "<td>" . ($row['fecha_sync'] ?? 'N/A') . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                    <?php
                } else {
                    ?>
                    <div class="status-box warning">
                        <h3>⚠️ Tabla 'sincronizacion_log' no existe</h3>
                        <p>Esta tabla es necesaria para el sistema de sincronización.</p>
                        <a href="?accion=crear_tabla_sync" class="btn btn-green">Crear Tabla Ahora</a>
                    </div>
                    <?php
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // VER PENDIENTES
            // ====================================
            elseif ($accion === 'pendientes') {
                ?>
                <h2>⏳ Registros Pendientes de Sincronización</h2>
                
                <?php
                $result = $conn->query("SELECT * FROM sincronizacion_log WHERE sincronizado = 0 ORDER BY id_sync ASC");
                
                if ($result && $result->num_rows > 0) {
                    ?>
                    <p>Se encontraron <strong><?php echo $result->num_rows; ?></strong> registros pendientes:</p>
                    <table>
                        <tr>
                            <th>ID Sync</th>
                            <th>Tabla</th>
                            <th>Acción</th>
                            <th>ID Registro</th>
                            <th>Datos</th>
                            <th>Fecha</th>
                        </tr>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_sync']; ?></td>
                            <td><strong><?php echo $row['tabla']; ?></strong></td>
                            <td><span class="badge badge-info"><?php echo $row['accion']; ?></span></td>
                            <td><?php echo $row['id_registro']; ?></td>
                            <td><pre><?php echo htmlspecialchars(substr($row['datos'] ?? '{}', 0, 100)); ?></pre></td>
                            <td><?php echo $row['fecha_sync'] ?? 'N/A'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                    <?php
                } else {
                    ?>
                    <div class="status-box success">
                        <h3>✅ No hay registros pendientes</h3>
                        <p>Todos los cambios están sincronizados.</p>
                    </div>
                    <?php
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // CREAR PRUEBA
            // ====================================
            elseif ($accion === 'crear_prueba') {
                ?>
                <h2>🧪 Crear Registro de Prueba</h2>
                
                <?php
                // Verificar si existe la tabla de sincronización
                $tabla_sync_existe = $conn->query("SHOW TABLES LIKE 'sincronizacion_log'")->num_rows > 0;
                
                if (!$tabla_sync_existe) {
                    ?>
                    <div class="status-box error">
                        <p>❌ La tabla 'sincronizacion_log' no existe. Créala primero.</p>
                        <a href="?accion=crear_tabla_sync" class="btn btn-green">Crear Tabla</a>
                    </div>
                    <?php
                } else {
                    // Insertar registro de prueba
                    $datos_prueba = json_encode([
                        'nombre' => 'Producto de Prueba ' . date('Y-m-d H:i:s'),
                        'precio' => 99.99,
                        'stock' => 10,
                        'test' => true
                    ]);
                    
                    $stmt = $conn->prepare("INSERT INTO sincronizacion_log (tabla, accion, id_registro, datos, sincronizado) VALUES (?, ?, ?, ?, ?)");
                    $tabla = 'productos';
                    $accion_db = 'INSERT';
                    $id_reg = rand(9000, 9999);
                    $sinc = 0;
                    $stmt->bind_param('ssisi', $tabla, $accion_db, $id_reg, $datos_prueba, $sinc);
                    
                    if ($stmt->execute()) {
                        ?>
                        <div class="status-box success">
                            <h3>✅ Registro de prueba creado exitosamente</h3>
                            <p><strong>ID Sync:</strong> <?php echo $stmt->insert_id; ?></p>
                            <p><strong>Tabla:</strong> productos</p>
                            <p><strong>Acción:</strong> INSERT</p>
                            <p><strong>Datos:</strong></p>
                            <pre><?php echo htmlspecialchars($datos_prueba); ?></pre>
                            <p>Ahora puedes ejecutar la sincronización para enviar este registro a la nube.</p>
                            <a href="?accion=pendientes" class="btn btn-blue">Ver Pendientes</a>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="status-box error">
                            <p>❌ Error al crear registro: <?php echo $conn->error; ?></p>
                        </div>
                        <?php
                    }
                    $stmt->close();
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // COMPARAR DATOS
            // ====================================
            elseif ($accion === 'comparar') {
                ?>
                <h2>🔄 Comparación de Datos</h2>
                <p>Tablas sincronizadas en la base de datos actual:</p>
                
                <?php
                $tablas_sync = ["categorias", "configuracion_empresa", "config_caja", "cortes", "page_titles", "productos", "subcategorias"];
                
                foreach ($tablas_sync as $tabla) {
                    $check = $conn->query("SHOW TABLES LIKE '$tabla'");
                    if ($check && $check->num_rows > 0) {
                        $count = $conn->query("SELECT COUNT(*) as c FROM $tabla")->fetch_assoc()['c'];
                        echo "<div class='card'>";
                        echo "<h3>📦 $tabla</h3>";
                        echo "<p><strong>Registros:</strong> <span class='stat-number' style='font-size: 1.5em;'>$count</span></p>";
                        echo "</div>";
                    }
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // VER ESTRUCTURA
            // ====================================
            elseif ($accion === 'estructura') {
                ?>
                <h2>📝 Estructura de sincronizacion_log</h2>
                
                <?php
                $check = $conn->query("SHOW TABLES LIKE 'sincronizacion_log'");
                if ($check && $check->num_rows > 0) {
                    $columns = $conn->query("SHOW COLUMNS FROM sincronizacion_log");
                    ?>
                    <table>
                        <tr>
                            <th>Columna</th>
                            <th>Tipo</th>
                            <th>Null</th>
                            <th>Key</th>
                            <th>Default</th>
                        </tr>
                        <?php while ($col = $columns->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $col['Field']; ?></strong></td>
                            <td><?php echo $col['Type']; ?></td>
                            <td><?php echo $col['Null']; ?></td>
                            <td><?php echo $col['Key']; ?></td>
                            <td><?php echo $col['Default'] ?? 'NULL'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                    <?php
                } else {
                    ?>
                    <div class="status-box warning">
                        <p>⚠️ La tabla 'sincronizacion_log' no existe.</p>
                        <a href="?accion=crear_tabla_sync" class="btn btn-green">Crear Tabla</a>
                    </div>
                    <?php
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // TEST ENDPOINT
            // ====================================
            elseif ($accion === 'test_endpoint') {
                ?>
                <h2>🌐 Test de Conectividad con la Nube</h2>
                
                <?php
                $endpoint_url = 'https://vendingbox.online/sistema/sincronizar_local.php';
                
                echo "<p>Probando conexión con: <strong>$endpoint_url</strong></p>";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($http_code === 200 && $response) {
                    ?>
                    <div class="status-box success">
                        <h3>✅ Conexión exitosa</h3>
                        <p><strong>HTTP Code:</strong> <?php echo $http_code; ?></p>
                        <p><strong>Respuesta (primeros 500 caracteres):</strong></p>
                        <pre><?php echo htmlspecialchars(substr($response, 0, 500)); ?></pre>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="status-box error">
                        <h3>❌ Error de conexión</h3>
                        <p><strong>HTTP Code:</strong> <?php echo $http_code; ?></p>
                        <?php if ($error): ?>
                        <p><strong>Error:</strong> <?php echo $error; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            
            // ====================================
            // CREAR TABLA SYNC
            // ====================================
            elseif ($accion === 'crear_tabla_sync') {
                ?>
                <h2>🔨 Crear Tabla de Sincronización</h2>
                
                <?php
                $sql = "CREATE TABLE IF NOT EXISTS sincronizacion_log (
                    id_sync INT AUTO_INCREMENT PRIMARY KEY,
                    tabla VARCHAR(100) NOT NULL,
                    accion VARCHAR(20) NOT NULL,
                    id_registro INT NOT NULL,
                    datos TEXT,
                    sincronizado TINYINT DEFAULT 0,
                    fecha_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_sincronizado (sincronizado),
                    INDEX idx_tabla (tabla)
                )";
                
                if ($conn->query($sql)) {
                    ?>
                    <div class="status-box success">
                        <h3>✅ Tabla creada exitosamente</h3>
                        <p>La tabla 'sincronizacion_log' ha sido creada y está lista para usar.</p>
                        <a href="?accion=estado" class="btn btn-blue">Ver Estado</a>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="status-box error">
                        <h3>❌ Error al crear tabla</h3>
                        <p><?php echo $conn->error; ?></p>
                    </div>
                    <?php
                }
                ?>
                
                <a href="?" class="btn">« Volver</a>
                <?php
            }
            ?>
        </div>
    </div>
</body>
</html>
