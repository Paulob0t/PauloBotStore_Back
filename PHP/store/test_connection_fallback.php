<?php
/**
 * 🔍 TEST RÁPIDO DE CONEXIÓN CON FALLBACK
 */
header('Content-Type: application/json; charset=utf-8');

// Limpiar buffer de errores
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla, solo en JSON

$resultado = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ambiente' => strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'WINDOWS' : 'LINUX',
    'conexiones' => [],
    'conexion_activa' => null,
    'productos_destacados' => [],
    'status' => 'error'
];

// Probar conexión LOCAL
$conn_local = @new mysqli('localhost', 'root', '', 'colegos_vending');
if (!$conn_local->connect_error) {
    $resultado['conexiones']['local'] = [
        'status' => 'OK',
        'host' => $conn_local->host_info,
        'db' => 'colegos_vending'
    ];
} else {
    $resultado['conexiones']['local'] = [
        'status' => 'ERROR',
        'error' => $conn_local->connect_error,
        'errno' => $conn_local->connect_errno
    ];
}

// Probar conexión NUBE
$conn_nube = @new mysqli('cpanel.colegos.com.mx', 'colegos_vending', 'IfbUK2ClF~bV', 'colegos_vending', 3306);
if (!$conn_nube->connect_error) {
    $resultado['conexiones']['nube'] = [
        'status' => 'OK',
        'host' => $conn_nube->host_info,
        'db' => 'colegos_vending'
    ];
} else {
    $resultado['conexiones']['nube'] = [
        'status' => 'ERROR',
        'error' => $conn_nube->connect_error,
        'errno' => $conn_nube->connect_errno
    ];
}

// Seleccionar conexión activa (LOCAL primero, NUBE como fallback)
$conn = null;
if (!$conn_local->connect_error) {
    $conn = $conn_local;
    $resultado['conexion_activa'] = 'LOCAL (vending)';
    $resultado['status'] = 'ok';
} elseif (!$conn_nube->connect_error) {
    $conn = $conn_nube;
    $resultado['conexion_activa'] = 'NUBE (colegos_vending) - FALLBACK';
    $resultado['status'] = 'ok';
    $resultado['advertencia'] = 'Trabajando directo en producción - considera crear BD local';
} else {
    $resultado['status'] = 'error';
    $resultado['error'] = 'No hay ninguna conexión disponible';
}

// Si hay conexión, obtener productos destacados
if ($conn) {
    $conn->set_charset('utf8mb4');
    
    // Consultar productos destacados
    $sql = "SELECT id_producto, nombre_producto, precio, destacado, orden_destacado 
            FROM productos 
            WHERE activo = 1 
            AND destacado = 1 
            AND orden_destacado IS NOT NULL
            ORDER BY orden_destacado ASC
            LIMIT 5";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $resultado['productos_destacados'][] = $row;
        }
        $resultado['total_destacados'] = count($resultado['productos_destacados']);
    } else {
        $resultado['error_query'] = $conn->error;
    }
    
    $conn->close();
}

// Cerrar conexiones
if ($conn_local && !$conn_local->connect_error) $conn_local->close();
if ($conn_nube && !$conn_nube->connect_error) $conn_nube->close();

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
