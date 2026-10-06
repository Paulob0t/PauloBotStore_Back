<?php
/**
 * Test de Comandos Directos al MEI CF 7000
 * Permite enviar comandos arbitrarios al dispositivo
 */

// Prevenir output antes de headers
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir función de envío serial
require_once __DIR__ . '/monedero_api.php';

$comando = $_POST['comando'] ?? '';

if (empty($comando)) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'No se proporcionó ningún comando'
    ]);
    exit;
}

// Log del comando
error_log("🧪 TEST: Comando manual: $comando");

// Enviar comando
$resultado = enviarComandoSerial($comando);

echo json_encode([
    'success' => $resultado['success'],
    'comando' => $comando,
    'output' => $resultado['output'],
    'mensaje' => $resultado['success'] ? 'Comando ejecutado' : 'Error al ejecutar comando'
]);
