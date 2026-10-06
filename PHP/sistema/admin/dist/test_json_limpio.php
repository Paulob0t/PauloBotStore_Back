<?php
/**
 * Test JSON limpio - Verificar que no hay output extra
 * Sube a: vendingbox.online/sistema/admin/dist/test_json_limpio.php
 * Ejecuta y verifica que SOLO veas JSON puro (sin <br>, sin warnings)
 */

// Configuración igual que guardar_producto.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
session_start();
require_once 'db_config_dual.php';
ob_end_clean();

header('Content-Type: application/json');

// Test simple
echo json_encode([
    'success' => true,
    'mensaje' => '✅ JSON LIMPIO - Si ves esto sin errores, el problema está solucionado',
    'timestamp' => date('Y-m-d H:i:s'),
    'using_db' => USING_DB,
    'test_query' => 'OK'
]);
?>
