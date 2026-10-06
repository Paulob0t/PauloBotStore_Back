<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once __DIR__ . '/db_config_dual.php';

try {
    $sql = "SELECT nombre_empresa, direccion, ciudad, estado, telefono, rfc, website
            FROM configuracion_empresa
            WHERE activo = 1
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => [
                'nombre_empresa' => 'VENDING BOX',
                'direccion' => '',
                'ciudad' => '',
                'estado' => '',
                'telefono' => '',
                'rfc' => '',
                'website' => 'www.vendigbox.com'
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn);
