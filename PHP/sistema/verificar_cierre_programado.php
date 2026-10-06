<?php
/**
 * Verificar y ejecutar cierre automático basado en tiempo
 * Este endpoint se llama desde el frontend automáticamente
 */

header('Content-Type: application/json');
require_once 'admin/dist/db_config_dual.php';
require_once 'admin/dist/CorteCaja.class.php';

try {
    $corteCaja = new CorteCaja($conn);
    
    // Si solo piden información
    if (isset($_GET['info'])) {
        $config = $corteCaja->getConfig();
        $corte = $corteCaja->getCorteActual();
        
        $info = [
            'caja_activa' => $corteCaja->hayCajaActiva(),
            'hora_actual' => date('Y-m-d H:i:s')
        ];
        
        if ($corte) {
            $info['id_corte'] = $corte['id'];
            $info['fecha_apertura'] = $corte['fecha'];
            $info['hora_apertura'] = $corte['hora'];
            $info['hora_cierre_programada'] = $corte['hora_cierre_programada'];
            
            // Verificar si debe cerrar
            if ($corte['hora_cierre_programada']) {
                $info['debe_cerrar'] = time() >= strtotime($corte['hora_cierre_programada']);
            } else {
                $info['debe_cerrar'] = false;
            }
        }
        
        echo json_encode($info);
        exit;
    }
    
    // Verificar si hay una caja activa
    if (!$corteCaja->hayCajaActiva()) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'No hay caja activa'
        ]);
        exit;
    }
    
    // Ejecutar cierre automático si ya pasó la hora
    $resultado = $corteCaja->ejecutarCierreAutomatico();
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    $log_error = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    @file_put_contents(__DIR__ . '/logs/cortes_auto.log', $log_error, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
