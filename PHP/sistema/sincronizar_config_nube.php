<?php
/**
 * 🔄 SINCRONIZAR CONFIGURACIÓN DESDE LA NUBE
 * 
 * Este script obtiene la configuración de la caja desde la Nube
 * y la aplica en el sistema local
 * 
 * Debe ejecutarse periódicamente (cada minuto) para mantener sincronizado
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detectar si se ejecuta desde navegador o CLI
$es_web = php_sapi_name() !== 'cli';

if ($es_web) {
    header('Content-Type: application/json');
}

// ============================================================================
// CONFIGURACIÓN
// ============================================================================

define('URL_NUBE', 'https://adminvending.colegos.com.mx');
define('SYNC_TOKEN', 'VendigBoxNube2025_ChangeThis'); // ⚠️ Cambiar en producción

$log_file = __DIR__ . '/logs/sync_config_nube.log';
$log_dir = dirname($log_file);

if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message, $tipo = 'INFO') {
    global $log_file, $es_web;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$tipo}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    if (!$es_web) {
        echo $log_entry;
    }
}

// ============================================================================
// CONEXIÓN LOCAL
// ============================================================================

require_once __DIR__ . '/admin/dist/db_config_dual.php';

// ============================================================================
// OBTENER CONFIGURACIÓN DESDE LA NUBE
// ============================================================================

function obtener_config_nube() {
    $url = URL_NUBE . '/obtener_config_caja.php';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Sync-Token: ' . SYNC_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($http_code !== 200) {
        $error_msg = "Error HTTP {$http_code} al obtener config de la Nube";
        if ($curl_error) {
            $error_msg .= " - cURL: {$curl_error}";
        }
        if ($response) {
            $error_msg .= " - Response: " . substr($response, 0, 200);
        }
        throw new Exception($error_msg);
    }
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['success'])) {
        throw new Exception("Respuesta inválida de la Nube: " . substr($response, 0, 200));
    }
    
    return $data;
}

// ============================================================================
// APLICAR CONFIGURACIÓN LOCAL
// ============================================================================

function aplicar_config_local($config, $conn) {
    $sql = "UPDATE config_caja SET 
            auto_apertura_caja = ?,
            horas_para_cierre = ?,
            corte_automatico_habilitado = ?,
            hora_corte_automatico = ?,
            monto_inicial_default = ?
            WHERE id = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'iiisd',
        $config['auto_apertura_caja'],
        $config['horas_para_cierre'],
        $config['corte_automatico_habilitado'],
        $config['hora_corte_automatico'],
        $config['monto_inicial_default']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar config local: " . $stmt->error);
    }
    
    return $stmt->affected_rows;
}

// ============================================================================
// EJECUCIÓN PRINCIPAL
// ============================================================================

log_message("========================================");
log_message("INICIO - Sincronización de configuración", "START");

try {
    // Obtener config de la Nube
    log_message("Obteniendo configuración desde la Nube...");
    $response = obtener_config_nube();
    
    if (!$response['success']) {
        throw new Exception($response['error'] ?? 'Error desconocido');
    }
    
    $config_nube = $response['config'];
    log_message("✓ Configuración obtenida de la Nube");
    log_message("  - Auto apertura: " . ($config_nube['auto_apertura_caja'] ? 'SÍ' : 'NO'));
    log_message("  - Horas para cierre: {$config_nube['horas_para_cierre']}h");
    log_message("  - Corte automático: " . ($config_nube['corte_automatico_habilitado'] ? 'HABILITADO' : 'DESHABILITADO'));
    log_message("  - Hora corte: {$config_nube['hora_corte_automatico']}");
    log_message("  - Monto inicial: \${$config_nube['monto_inicial_default']}");
    
    // Aplicar en BD local
    log_message("Aplicando configuración en BD local...");
    $rows_affected = aplicar_config_local($config_nube, $conn);
    
    if ($rows_affected > 0) {
        log_message("✓ Configuración sincronizada correctamente", "SUCCESS");
        $mensaje = "Configuración actualizada desde la Nube";
    } else {
        log_message("✓ Configuración ya estaba sincronizada (sin cambios)", "INFO");
        $mensaje = "Configuración ya estaba actualizada";
    }
    
    log_message("FIN - Sincronización completada", "END");
    log_message("========================================");
    
    $resultado = [
        'success' => true,
        'message' => $mensaje,
        'config_aplicada' => $config_nube,
        'rows_affected' => $rows_affected
    ];
    
} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage(), "ERROR");
    log_message("FIN - Sincronización con errores", "END");
    log_message("========================================");
    
    $resultado = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

$conn->close();

if ($es_web) {
    echo json_encode($resultado, JSON_PRETTY_PRINT);
} else {
    echo json_encode($resultado) . "\n";
}
?>
