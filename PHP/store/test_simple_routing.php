<?php
/**
 * Test SIMPLE de monedero_api.php
 * Verifica si el routing funciona
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🧪 Test Simple de Routing</h2>";

// Test 1: Verificar que el archivo existe
$apiPath = __DIR__ . '/monedero_api.php';
echo "<h3>1️⃣ Verificar archivo</h3>";
echo "<p>Ruta: <code>$apiPath</code></p>";
echo "<p>Existe: " . (file_exists($apiPath) ? "✅ Sí" : "❌ No") . "</p>";

// Test 2: Llamar directamente con file_get_contents
echo "<h3>2️⃣ Llamada GET (get_saldo)</h3>";
$url1 = 'http://localhost/vendingbox.online/sistema/monedero_api.php?action=get_saldo';
echo "<p>URL: <code>$url1</code></p>";
$response1 = @file_get_contents($url1);
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo htmlspecialchars($response1 ?: "Sin respuesta");
echo "</pre>";

// Test 3: Llamar con POST simple
echo "<h3>3️⃣ Llamada POST (dispensar_cambio)</h3>";
$url2 = 'http://localhost/vendingbox.online/sistema/monedero_api.php';
$data = ['action' => 'dispensar_cambio', 'monto' => 5];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$response2 = @file_get_contents($url2, false, $context);

echo "<p>Datos enviados: <code>" . json_encode($data) . "</code></p>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo htmlspecialchars($response2 ?: "Sin respuesta");
echo "</pre>";

// Test 4: Ver logs de Apache
echo "<h3>4️⃣ Logs de Apache (últimas 20 líneas)</h3>";
$logPath = 'C:/xampp/apache/logs/error.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background: #fff3e0; padding: 10px; max-height: 300px; overflow: auto; font-size: 11px;'>";
    foreach ($lastLines as $line) {
        if (stripos($line, 'monedero') !== false || stripos($line, 'dispensar') !== false) {
            echo "<strong style='color: red;'>" . htmlspecialchars($line) . "</strong>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: red;'>No se encontró el archivo de log</p>";
}

// Test 5: Verificar extensiones de PHP
echo "<h3>5️⃣ Extensiones PHP necesarias</h3>";
echo "<ul>";
echo "<li>JSON: " . (extension_loaded('json') ? "✅" : "❌") . "</li>";
echo "<li>cURL: " . (extension_loaded('curl') ? "✅" : "❌") . "</li>";
echo "<li>mbstring: " . (extension_loaded('mbstring') ? "✅" : "❌") . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='test_dispensar_debug.php?monto=5'>← Volver al test de debug</a></p>";
?>
