<?php
/**
 * Ver logs de error de Apache en tiempo real
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$logFile = 'C:/xampp/apache/logs/error.log';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>🔍 Logs en Tiempo Real</title>";
echo "<style>
body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
h2 { color: #4EC9B0; }
pre { 
    background: #000; 
    color: #0f0; 
    padding: 15px; 
    border-radius: 5px; 
    overflow: auto;
    max-height: 600px;
    font-size: 12px;
    line-height: 1.4;
}
.error { color: #f44336; font-weight: bold; }
.warning { color: #ff9800; }
.success { color: #4caf50; font-weight: bold; }
.info { color: #2196f3; }
.debug { color: #9c27b0; }
button { 
    background: #4caf50; 
    color: white; 
    border: none; 
    padding: 10px 20px; 
    cursor: pointer;
    border-radius: 5px;
    font-size: 14px;
    margin: 5px;
}
button:hover { background: #45a049; }
.clear-btn { background: #f44336; }
.clear-btn:hover { background: #da190b; }
</style>";
echo "</head><body>";

echo "<h2>🔍 Logs de Apache - Tiempo Real</h2>";
echo "<p><strong>Archivo:</strong> <code>$logFile</code></p>";

if (!file_exists($logFile)) {
    echo "<p style='color: red;'>❌ Archivo de log no encontrado</p>";
    exit;
}

// Botones
echo "<div>";
echo "<button onclick='location.reload()'>🔄 Refrescar</button>";
echo "<button onclick='clearLogs()' class='clear-btn'>🗑️ Limpiar Logs</button>";
echo "<button onclick='window.open(\"test_dispensar_debug.php?monto=5\")'>🧪 Test Dispensar</button>";
echo "<button onclick='window.open(\"test_simple_routing.php\")'>🔧 Test Routing</button>";
echo "</div>";

echo "<h3>📜 Últimas 100 líneas (filtradas):</h3>";

// Leer últimas 100 líneas
$lines = file($logFile);
$recentLines = array_slice($lines, -100);

// Filtrar líneas relevantes
$filtered = [];
foreach ($recentLines as $line) {
    $lower = strtolower($line);
    if (
        strpos($lower, 'monedero') !== false ||
        strpos($lower, 'dispensar') !== false ||
        strpos($lower, 'cambio') !== false ||
        strpos($lower, 'inventario') !== false ||
        strpos($lower, 'coin') !== false ||
        strpos($lower, 'error') !== false ||
        strpos($lower, 'warning') !== false ||
        strpos($lower, 'fatal') !== false ||
        strpos($line, '🎯') !== false ||
        strpos($line, '💰') !== false ||
        strpos($line, '✅') !== false ||
        strpos($line, '❌') !== false ||
        strpos($line, '📂') !== false ||
        strpos($line, '📁') !== false ||
        strpos($line, '💾') !== false
    ) {
        $filtered[] = $line;
    }
}

if (empty($filtered)) {
    echo "<pre>No hay logs relevantes de monedero/dispensar en las últimas 100 líneas.</pre>";
} else {
    echo "<pre>";
    foreach ($filtered as $line) {
        $line = htmlspecialchars($line);
        
        // Colorear según tipo
        if (stripos($line, 'error') !== false || strpos($line, '❌') !== false) {
            echo "<span class='error'>$line</span>";
        } elseif (stripos($line, 'warning') !== false || strpos($line, '⚠️') !== false) {
            echo "<span class='warning'>$line</span>";
        } elseif (strpos($line, '✅') !== false || stripos($line, 'success') !== false) {
            echo "<span class='success'>$line</span>";
        } elseif (strpos($line, '🔍') !== false || strpos($line, '📂') !== false || strpos($line, '📁') !== false) {
            echo "<span class='info'>$line</span>";
        } elseif (strpos($line, '🎯') !== false || strpos($line, '💰') !== false) {
            echo "<span class='debug'>$line</span>";
        } else {
            echo $line;
        }
    }
    echo "</pre>";
}

echo "<h3>📊 Todas las líneas (últimas 50):</h3>";
$allRecent = array_slice($lines, -50);
echo "<pre>";
foreach ($allRecent as $line) {
    echo htmlspecialchars($line);
}
echo "</pre>";

echo "<script>
function clearLogs() {
    if (confirm('¿Estás seguro de limpiar el log? Esto eliminará TODO el historial.')) {
        fetch('clear_logs.php')
            .then(() => location.reload())
            .catch(err => alert('Error: ' + err));
    }
}

// Auto-refresh cada 3 segundos
setTimeout(() => location.reload(), 3000);
</script>";

echo "</body></html>";
?>
