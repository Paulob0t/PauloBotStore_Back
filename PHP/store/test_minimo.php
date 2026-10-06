<?php
/**
 * Test MINIMO - Solo para verificar que PHP funciona
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "TEST 1: PHP funciona ✅<br>";

$action = $_GET['action'] ?? $_POST['action'] ?? 'ninguna';
echo "TEST 2: Action recibida: <strong>$action</strong><br>";

if ($action === 'dispensar_cambio') {
    echo "TEST 3: Action es dispensar_cambio ✅<br>";
    
    $monto = $_POST['monto'] ?? $_GET['monto'] ?? 0;
    echo "TEST 4: Monto recibido: <strong>$monto</strong><br>";
    
    // Intentar cargar monedero_api.php y ver si hay errores
    echo "<br><h3>Cargando funciones de monedero_api.php...</h3>";
    
    // Definir constantes primero
    define('SALDO_FILE', __DIR__ . '/admin/dist/logs/saldo_actual.json');
    define('SIGNAL_FILE', __DIR__ . '/admin/dist/logs/nueva_moneda_signal.json');
    define('COIN_INVENTORY_FILE', __DIR__ . '/admin/dist/logs/coin_inventory.log');
    
    echo "Constantes definidas ✅<br>";
    echo "COIN_INVENTORY_FILE: " . COIN_INVENTORY_FILE . "<br>";
    echo "Existe: " . (file_exists(COIN_INVENTORY_FILE) ? "✅ Sí" : "❌ No") . "<br>";
    
    // Crear directorio si no existe
    $dir = dirname(COIN_INVENTORY_FILE);
    if (!is_dir($dir)) {
        echo "Creando directorio: $dir<br>";
        mkdir($dir, 0755, true);
    }
    
    // Crear archivo de inventario si no existe
    if (!file_exists(COIN_INVENTORY_FILE)) {
        $defaultInventory = [
            'timestamp' => date('Y-m-d H:i:s'),
            'denominaciones' => [
                '1' => 10, '2' => 10, '5' => 10, '10' => 10, '20' => 5
            ],
            'total_pesos' => 205,
            'ultima_actualizacion' => date('Y-m-d H:i:s'),
            'log' => []
        ];
        file_put_contents(COIN_INVENTORY_FILE, json_encode($defaultInventory, JSON_PRETTY_PRINT));
        echo "Inventario inicial creado ✅<br>";
    }
    
    echo "<br><h3>Inventario actual:</h3>";
    $inv = json_decode(file_get_contents(COIN_INVENTORY_FILE), true);
    echo "<pre>" . json_encode($inv, JSON_PRETTY_PRINT) . "</pre>";
    
} else {
    echo "TEST 3: Action NO es dispensar_cambio, prueba con: ?action=dispensar_cambio&monto=5<br>";
}
?>
