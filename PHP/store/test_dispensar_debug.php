<?php
/**
 * Test de dispensado de cambio con debugging completo
 * Uso: http://localhost/vendingbox/test_dispensar_debug.php?monto=14
 */

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>🔍 Test de Dispensado de Cambio - DEBUG</h2>";

$monto = $_GET['monto'] ?? 14;
echo "<p><strong>Monto a dispensar:</strong> $$monto</p>";

echo "<h3>📡 Haciendo petición a monedero_api.php...</h3>";

$url = 'http://localhost/vendingbox/monedero_api.php?action=dispensar_cambio';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['monto' => $monto]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>📥 Respuesta HTTP:</h3>";
echo "<p><strong>Código:</strong> $httpCode</p>";

if ($error) {
    echo "<p style='color: red;'><strong>Error cURL:</strong> $error</p>";
}

echo "<h3>📄 Respuesta Raw:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h3>🔍 Análisis:</h3>";

// Intentar decodificar JSON
$json = json_decode($response, true);
$jsonError = json_last_error();

if ($jsonError === JSON_ERROR_NONE && $json !== null) {
    echo "<p style='color: green;'>✅ <strong>JSON válido</strong></p>";
    echo "<pre style='background: #e8f5e9; padding: 10px; border: 1px solid #4caf50;'>";
    echo htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "</pre>";
    
    if (isset($json['success']) && $json['success']) {
        echo "<h3>✅ Éxito!</h3>";
        if (isset($json['desglose'])) {
            echo "<p><strong>Desglose:</strong></p><ul>";
            foreach ($json['desglose'] as $denom => $cant) {
                echo "<li>$$denom × $cant = $" . ($denom * $cant) . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<h3>❌ Error en la respuesta</h3>";
        echo "<p><strong>Mensaje:</strong> " . ($json['mensaje'] ?? 'Sin mensaje') . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ <strong>JSON INVÁLIDO</strong></p>";
    echo "<p><strong>Error JSON:</strong> " . json_last_error_msg() . "</p>";
    
    // Buscar errores/warnings de PHP en la salida
    if (preg_match('/(Warning|Notice|Error|Fatal).*?on line \d+/i', $response, $matches)) {
        echo "<p style='color: red; font-weight: bold;'>🚨 Error PHP detectado:</p>";
        echo "<pre style='background: #ffebee; padding: 10px; border: 1px solid #f44336;'>";
        echo htmlspecialchars($matches[0]);
        echo "</pre>";
    }
    
    // Mostrar primeros 500 caracteres
    echo "<p><strong>Primeros 500 caracteres:</strong></p>";
    echo "<pre style='background: #fff3e0; padding: 10px; border: 1px solid #ff9800;'>";
    echo htmlspecialchars(substr($response, 0, 500));
    echo "</pre>";
}

echo "<hr>";
echo "<h3>🔧 Acciones:</h3>";
echo "<ul>";
echo "<li><a href='?monto=5'>Test con \$5</a></li>";
echo "<li><a href='?monto=10'>Test con \$10</a></li>";
echo "<li><a href='?monto=14'>Test con \$14</a></li>";
echo "<li><a href='?monto=25'>Test con \$25</a></li>";
echo "<li><a href='test_dispensar_cambio.php'>Ir a Test Completo</a></li>";
echo "<li><a href='cart.php'>Volver al Carrito</a></li>";
echo "</ul>";
?>
