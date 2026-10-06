<?php
// Endpoint para crear comando de prueba - DEBE estar al inicio antes de cualquier HTML
if (isset($_GET['crear_comando'])) {
    $queueFile = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
    
    $comando = [
        'timestamp' => microtime(true),
        'monto' => 5,
        'desglose' => ['5' => 1],
        'status' => 'PENDING'
    ];
    
    $result = @file_put_contents($queueFile, json_encode($comando, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $result !== false,
        'mensaje' => $result !== false 
            ? "✅ Comando creado en: $queueFile" 
            : "❌ Error al crear archivo",
        'archivo' => $queueFile,
        'bytes' => $result
    ]);
    exit;
}

// Definir rutas
$queueFile = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
$responseFile = __DIR__ . '/admin/dist/logs/monedero_dispense_response.json';
$listenerLog = __DIR__ . '/admin/dist/logs/monedero_listener.log';
$inventoryFile = __DIR__ . '/admin/dist/logs/coin_inventory.log';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>🔍 Diagnóstico Sistema de Dispensado</title>
    <style>
        body {
            font-family: 'Consolas', monospace;
            background: #0a0a0a;
            color: #fff;
            padding: 20px;
        }
        .section {
            background: #1a1a1a;
            border: 2px solid #F6DA01;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .ok { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #FFA500; }
        .info { color: #00bfff; }
        h2 { color: #F6DA01; }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .code {
            font-family: 'Consolas', monospace;
            background: #2a2a2a;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Sistema de Dispensado</h1>
    <p class="info">Verificando configuración del sistema de dispensado de cambio...</p>

    <!-- 1. VERIFICAR PROCESOS -->
    <div class="section">
        <h2>1️⃣ PROCESOS EN EJECUCIÓN</h2>
        <?php
        exec('tasklist /FI "IMAGENAME eq MonederoMonitor.exe" 2>&1', $outputMonitor);
        exec('tasklist /FI "IMAGENAME eq php.exe" 2>&1', $outputPhp);
        exec('tasklist /FI "IMAGENAME eq powershell.exe" 2>&1', $outputPs);
        
        $monitorRunning = false;
        $phpCount = 0;
        $psCount = 0;
        
        foreach ($outputMonitor as $line) {
            if (stripos($line, 'MonederoMonitor.exe') !== false) {
                echo "<div class='ok'>✅ MonederoMonitor.exe está corriendo</div>";
                echo "<pre class='info'>" . htmlspecialchars($line) . "</pre>";
                $monitorRunning = true;
            }
        }
        if (!$monitorRunning) {
            echo "<div class='error'>❌ MonederoMonitor.exe NO está corriendo</div>";
            echo "<div class='warning'>⚠️ Debes ejecutar: <span class='code'>MonederoMonitor.bat</span></div>";
        }
        
        foreach ($outputPhp as $line) {
            if (stripos($line, 'php.exe') !== false) {
                $phpCount++;
            }
        }
        echo "<div class='" . ($phpCount > 0 ? 'ok' : 'error') . "'>";
        echo ($phpCount > 0 ? "✅" : "❌") . " PHP Processes: $phpCount</div>";
        
        foreach ($outputPs as $line) {
            if (stripos($line, 'powershell.exe') !== false) {
                $psCount++;
            }
        }
        echo "<div class='" . ($psCount > 0 ? 'ok' : 'error') . "'>";
        echo ($psCount > 0 ? "✅" : "❌") . " PowerShell Processes: $psCount</div>";
        ?>
    </div>

    <!-- 2. ARCHIVOS DE COMUNICACIÓN -->
    <div class="section">
        <h2>2️⃣ ARCHIVOS DE COMUNICACIÓN IPC</h2>
        
        <h3>📄 Archivo de Cola (Queue):</h3>
        <p class="code"><?php echo $queueFile; ?></p>
        <?php
        if (file_exists($queueFile)) {
            echo "<div class='warning'>⚠️ Existe (debería eliminarse después de procesar)</div>";
            $queueContent = file_get_contents($queueFile);
            echo "<pre>" . htmlspecialchars($queueContent) . "</pre>";
            
            $queueData = json_decode($queueContent, true);
            if ($queueData) {
                $edad = microtime(true) - $queueData['timestamp'];
                echo "<div class='info'>🕒 Edad del comando: " . round($edad, 2) . " segundos</div>";
                
                if ($edad > 10) {
                    echo "<div class='error'>❌ PROBLEMA: El comando tiene más de 10 segundos sin procesar</div>";
                    echo "<div class='warning'>💡 El listener PowerShell NO está leyendo la cola</div>";
                }
            }
        } else {
            echo "<div class='ok'>✅ No existe (OK - no hay comandos pendientes)</div>";
        }
        
        echo "<h3>📄 Archivo de Respuesta (Response):</h3>";
        echo "<p class='code'>" . $responseFile . "</p>";
        if (file_exists($responseFile)) {
            echo "<div class='ok'>✅ Existe (última respuesta del listener)</div>";
            $responseContent = file_get_contents($responseFile);
            echo "<pre>" . htmlspecialchars($responseContent) . "</pre>";
        } else {
            echo "<div class='warning'>⚠️ No existe (nunca se ha dispensado cambio)</div>";
        }
        ?>
    </div>

    <!-- 3. LOGS DEL LISTENER -->
    <div class="section">
        <h2>3️⃣ LOGS DEL LISTENER (Últimas 30 líneas)</h2>
        <p class="code"><?php echo $listenerLog; ?></p>
        <?php
        if (file_exists($listenerLog)) {
            $logLines = file($listenerLog);
            $lastLines = array_slice($logLines, -30);
            
            echo "<div class='ok'>✅ Archivo de log existe (" . count($logLines) . " líneas totales)</div>";
            echo "<pre>";
            foreach ($lastLines as $line) {
                // Colorear según tipo de mensaje
                if (stripos($line, 'ERROR') !== false || stripos($line, '❌') !== false) {
                    echo "<span class='error'>" . htmlspecialchars($line) . "</span>";
                } elseif (stripos($line, 'DISPENSE') !== false || stripos($line, 'DISPENSAR') !== false) {
                    echo "<span class='warning'>" . htmlspecialchars($line) . "</span>";
                } elseif (stripos($line, 'HEARTBEAT') !== false) {
                    echo "<span class='info'>" . htmlspecialchars($line) . "</span>";
                } else {
                    echo htmlspecialchars($line);
                }
            }
            echo "</pre>";
            
            // Buscar mensajes de dispensado en todo el log
            $dispenseLines = array_filter($logLines, function($line) {
                return stripos($line, 'DISPENSE') !== false || stripos($line, 'DISPENSAR') !== false;
            });
            
            if (count($dispenseLines) > 0) {
                echo "<div class='ok'>✅ Se encontraron " . count($dispenseLines) . " líneas de dispensado</div>";
            } else {
                echo "<div class='error'>❌ NO se encontraron líneas de dispensado en el log</div>";
                echo "<div class='warning'>💡 El PowerShell NO está ejecutando la lógica de dispensado</div>";
            }
        } else {
            echo "<div class='error'>❌ Archivo de log no existe</div>";
            echo "<div class='warning'>⚠️ El listener nunca se ha ejecutado</div>";
        }
        ?>
    </div>

    <!-- 4. INVENTARIO -->
    <div class="section">
        <h2>4️⃣ INVENTARIO DE MONEDAS</h2>
        <p class="code"><?php echo $inventoryFile; ?></p>
        <?php
        if (file_exists($inventoryFile)) {
            $inventoryContent = file_get_contents($inventoryFile);
            $inventory = json_decode($inventoryContent, true);
            
            if ($inventory) {
                echo "<div class='ok'>✅ Inventario válido</div>";
                echo "<table style='color:#fff; border-collapse:collapse; margin:10px 0;'>";
                echo "<tr style='background:#F6DA01; color:#000;'><th style='padding:8px;'>Denominación</th><th style='padding:8px;'>Cantidad</th><th style='padding:8px;'>Total</th></tr>";
                
                foreach ($inventory['denominaciones'] as $denom => $qty) {
                    $total = $denom * $qty;
                    echo "<tr style='background:#2a2a2a;'>";
                    echo "<td style='padding:8px; text-align:center;'>$$denom</td>";
                    echo "<td style='padding:8px; text-align:center;'>$qty</td>";
                    echo "<td style='padding:8px; text-align:center;'>$$total</td>";
                    echo "</tr>";
                }
                
                echo "<tr style='background:#F6DA01; color:#000; font-weight:bold;'>";
                echo "<td colspan='2' style='padding:8px;'>TOTAL</td>";
                echo "<td style='padding:8px; text-align:center;'>$" . $inventory['total_pesos'] . "</td>";
                echo "</tr>";
                echo "</table>";
                
                echo "<div class='info'>🕒 Última actualización: " . $inventory['ultima_actualizacion'] . "</div>";
            } else {
                echo "<div class='error'>❌ JSON inválido</div>";
            }
        } else {
            echo "<div class='error'>❌ No existe</div>";
        }
        ?>
    </div>

    <!-- 5. DIAGNÓSTICO -->
    <div class="section">
        <h2>5️⃣ DIAGNÓSTICO Y RECOMENDACIONES</h2>
        <?php
        $problemas = [];
        
        if (!$monitorRunning) {
            $problemas[] = "MonederoMonitor.exe no está corriendo → Ejecuta <code>MonederoMonitor.bat</code>";
        }
        
        if (file_exists($queueFile)) {
            $queueData = json_decode(file_get_contents($queueFile), true);
            if ($queueData && (microtime(true) - $queueData['timestamp']) > 10) {
                $problemas[] = "Archivo de cola antiguo → El listener PowerShell NO está procesando comandos";
            }
        }
        
        if (file_exists($listenerLog)) {
            $logContent = file_get_contents($listenerLog);
            if (stripos($logContent, 'DISPENSE') === false && stripos($logContent, 'DISPENSAR') === false) {
                $problemas[] = "No hay mensajes de dispensado en logs → El script PowerShell no tiene la lógica integrada";
            }
        }
        
        if (count($problemas) == 0) {
            echo "<div class='ok'>✅ No se detectaron problemas obvios</div>";
            echo "<div class='info'>💡 Si aún no dispensa, revisa los logs en tiempo real ejecutando el test nuevamente</div>";
        } else {
            echo "<div class='error'>❌ Problemas detectados:</div>";
            echo "<ul>";
            foreach ($problemas as $p) {
                echo "<li class='warning'>$p</li>";
            }
            echo "</ul>";
        }
        ?>
    </div>

    <!-- 6. PRUEBA MANUAL -->
    <div class="section">
        <h2>6️⃣ PRUEBA MANUAL DE DISPENSADO</h2>
        <p>Puedes crear manualmente un archivo de cola para probar:</p>
        <button onclick="crearComandoPrueba()" style="background:#F6DA01; color:#000; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">
            🧪 Crear Comando de Prueba ($5)
        </button>
        <div id="resultado" style="margin-top:10px;"></div>
    </div>

    <script>
        function crearComandoPrueba() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?crear_comando=1')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('resultado').innerHTML = 
                        `<div class="${data.success ? 'ok' : 'error'}">${data.mensaje}</div>
                         <p class="info">Espera 2 segundos y <a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="color:#F6DA01;">recarga la página</a> para ver si se procesó</p>`;
                })
                .catch(err => {
                    document.getElementById('resultado').innerHTML = 
                        `<div class="error">❌ Error: ${err.message}</div>`;
                });
        }
    </script>

    <div style="margin-top:20px; padding:10px; background:#2a2a2a; border-radius:5px;">
        <p class="info">📖 <strong>Flujo esperado:</strong></p>
        <ol class="info">
            <li>cart.php llama a monedero_api.php</li>
            <li>monedero_api.php crea <code>monedero_dispense_queue.json</code></li>
            <li>monedero_ps_listener.ps1 detecta archivo cada 500ms</li>
            <li>PowerShell dispensa físicamente usando COM5</li>
            <li>PowerShell crea <code>monedero_dispense_response.json</code></li>
            <li>monedero_api.php lee respuesta y retorna al frontend</li>
        </ol>
    </div>

</body>
</html>
