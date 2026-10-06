#!/usr/bin/php
<?php
/**
 * ⏰ CRON JOB - VERIFICAR CIERRE AUTOMÁTICO DE CAJA
 * 
 * Este script debe ejecutarse cada minuto desde cPanel/WHM
 * 
 * Configuración en cPanel Cron Jobs:
 * Comando: /usr/bin/php /home/tuusuario/public_html/cron_verificar_cierre.php
 * Frecuencia: * * * * * (cada minuto)
 * 
 * O también puedes usar:
 * Comando: wget -q -O /dev/null https://tudominio.com/cron_verificar_cierre.php
 * 
 * @author Sistema VendigBox
 * @version 1.0
 */

// Prevenir acceso directo desde navegador (opcional)
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_secret'])) {
    // Si quieres ejecutarlo desde URL, agrega: ?cron_secret=tu_clave_secreta
    $secret = 'VendigBox2025'; // Cambia esto por tu propia clave
    if (!isset($_GET['cron_secret']) || $_GET['cron_secret'] !== $secret) {
        http_response_code(403);
        die('Acceso denegado');
    }
}

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Logs
$log_file = __DIR__ . '/logs/cron_cierre_automatico.log';
$log_dir = dirname($log_file);

if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry; // Para logs de cPanel
}

log_message("========================================");
log_message("INICIO - Verificación de cierre automático");

try {
    // Incluir configuración de base de datos
    require_once __DIR__ . '/admin/dist/db_config_dual.php';
    require_once __DIR__ . '/admin/class/CorteCaja.class.php';
    
    // Crear instancia de CorteCaja
    $corteCaja = new CorteCaja($conn);
    
    // Verificar si hay un corte que debe cerrarse
    if ($corteCaja->verificarCierreAutomatico()) {
        log_message("✅ Se encontró un corte que debe cerrarse automáticamente");
        
        // Ejecutar el cierre automático
        $resultado = $corteCaja->ejecutarCierreAutomatico();
        
        if ($resultado['success']) {
            log_message("✅ ÉXITO: " . $resultado['message']);
            log_message("   ID Corte cerrado: " . ($resultado['id_corte'] ?? 'N/A'));
        } else {
            log_message("❌ ERROR: " . $resultado['message']);
        }
    } else {
        log_message("ℹ️  No hay cortes que requieran cierre automático en este momento");
    }
    
} catch (Exception $e) {
    log_message("❌ EXCEPCIÓN: " . $e->getMessage());
    log_message("   Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
}

log_message("FIN - Verificación completada");
log_message("========================================\n");

// Cerrar conexión
if (isset($conn) && $conn) {
    $conn->close();
}

exit(0);
