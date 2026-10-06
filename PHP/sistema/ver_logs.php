<?php
/**
 * Visor de logs de PHP en tiempo real
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ver Logs PHP</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        button { margin: 10px; padding: 10px; font-size: 16px; }
    </style>
</head>
<body>
    <h1>📋 Logs de PHP</h1>
    <button onclick="location.reload()">🔄 Recargar</button>
    <button onclick="document.getElementById('logs').innerHTML = ''">🗑️ Limpiar pantalla</button>
    <hr>
    <pre id="logs"><?php
    
    // Intentar obtener la ubicación del log de errores
    $log_file = ini_get('error_log');
    
    if (!$log_file || $log_file === 'syslog') {
        // Intentar ubicaciones comunes
        $possible_logs = [
            __DIR__ . '/error_log',
            __DIR__ . '/../error_log',
            'C:/xampp/php/logs/php_error_log',
            'C:/wamp/logs/php_error.log',
            '/var/log/php_errors.log',
            '/tmp/php_errors.log'
        ];
        
        foreach ($possible_logs as $possible_log) {
            if (file_exists($possible_log)) {
                $log_file = $possible_log;
                break;
            }
        }
    }
    
    echo "📁 Archivo de log: " . ($log_file ?: 'No encontrado') . "\n";
    echo "📍 Directorio actual: " . __DIR__ . "\n";
    echo "🕐 " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 80) . "\n\n";
    
    if ($log_file && file_exists($log_file)) {
        // Leer las últimas 100 líneas
        $lines = file($log_file);
        $last_lines = array_slice($lines, -100);
        
        foreach ($last_lines as $line) {
            if (stripos($line, 'error') !== false) {
                echo '<span class="error">' . htmlspecialchars($line) . '</span>';
            } elseif (stripos($line, 'warning') !== false) {
                echo '<span class="warning">' . htmlspecialchars($line) . '</span>';
            } elseif (stripos($line, '===') !== false || stripos($line, 'ARDUINO') !== false) {
                echo '<span class="info">' . htmlspecialchars($line) . '</span>';
            } else {
                echo htmlspecialchars($line);
            }
        }
    } else {
        echo "❌ No se pudo encontrar el archivo de logs\n\n";
        echo "Configuración PHP:\n";
        echo "error_log = " . ini_get('error_log') . "\n";
        echo "log_errors = " . ini_get('log_errors') . "\n";
        echo "display_errors = " . ini_get('display_errors') . "\n";
    }
    
    ?></pre>
    
    <script>
        // Auto-recargar cada 5 segundos
        setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>
