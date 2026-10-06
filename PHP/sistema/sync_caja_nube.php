<?php
/**
 * 🔄 ENDPOINT DE SINCRONIZACIÓN LOCAL
 * 
 * Este archivo recibe las notificaciones desde la Nube cuando:
 * - Se cierra automáticamente una caja
 * - Se abre automáticamente una nueva caja
 * 
 * El sistema local debe mantener sincronizada su configuración
 * pero toda la lógica automática se ejecuta desde la Nube
 * 
 * @author Sistema VendigBox - Local
 * @version 1.0
 * @date 2025-11-13
 */

header('Content-Type: application/json');

// ============================================================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================================================

define('SYNC_TOKEN', 'VendigBoxNube2025_ChangeThis'); // ⚠️ DEBE COINCIDIR CON LA NUBE

// Verificar token de seguridad
$headers = getallheaders();
if (!isset($headers['X-Sync-Token']) || $headers['X-Sync-Token'] !== SYNC_TOKEN) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Token de sincronización inválido'
    ]));
}

// ============================================================================
// LOGS
// ============================================================================

$log_file = __DIR__ . '/logs/sync_nube.log';
$log_dir = dirname($log_file);

if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_sync($message, $tipo = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$tipo}] {$message}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// ============================================================================
// PROCESAR SOLICITUD
// ============================================================================

try {
    // Leer datos JSON del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    if (!isset($data['accion'])) {
        throw new Exception('Falta el parámetro accion');
    }
    
    log_sync("=== Nueva sincronización desde Nube ===", 'START');
    log_sync("Acción: {$data['accion']}", 'INFO');
    
    // Incluir archivos necesarios
    require_once __DIR__ . '/admin/dist/db_config_dual.php';
    require_once __DIR__ . '/admin/dist/CorteCaja.class.php';
    
    $corteCaja = new CorteCaja($conn);
    $accion = $data['accion'];
    
    // ========================================================================
    // MANEJAR DIFERENTES ACCIONES
    // ========================================================================
    
    switch ($accion) {
        
        case 'cierre_automatico':
            log_sync("📥 Procesando cierre automático desde Nube", 'SYNC');
            
            // Verificar si hay caja activa local
            if (!$corteCaja->hayCajaActiva()) {
                log_sync("⚠️ No hay caja activa local para cerrar", 'WARNING');
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'No hay caja activa local',
                    'sincronizado' => false
                ]);
                exit;
            }
            
            // Obtener datos del cierre
            $id_corte_nube = $data['id_corte'] ?? null;
            $corte_local = $corteCaja->getCorteActual();
            
            if ($corte_local) {
                // Cerrar la caja local
                $resultado = $corteCaja->cerrarCaja(
                    $corte_local['id_usuario'],
                    "Cierre automático sincronizado desde Nube (Corte Nube ID: {$id_corte_nube})"
                );
                
                if ($resultado['success']) {
                    log_sync("✅ Caja local cerrada exitosamente", 'SUCCESS');
                    log_sync("   Corte local ID: {$corte_local['id']}", 'INFO');
                    
                    echo json_encode([
                        'success' => true,
                        'mensaje' => 'Caja local cerrada y sincronizada',
                        'id_corte_local' => $corte_local['id'],
                        'id_corte_nube' => $id_corte_nube
                    ]);
                } else {
                    log_sync("❌ Error al cerrar caja local: {$resultado['mensaje']}", 'ERROR');
                    echo json_encode([
                        'success' => false,
                        'error' => $resultado['mensaje']
                    ]);
                }
            }
            break;
            
        case 'apertura_automatica':
            log_sync("📥 Procesando apertura automática desde Nube", 'SYNC');
            
            // Verificar si ya hay caja activa
            if ($corteCaja->hayCajaActiva()) {
                log_sync("⚠️ Ya hay una caja activa local", 'WARNING');
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Ya existe caja activa local',
                    'sincronizado' => false
                ]);
                exit;
            }
            
            // Obtener datos de la apertura
            $id_corte_nube = $data['id_corte'] ?? null;
            $monto_inicial = $data['monto_inicial'] ?? 0;
            
            // Abrir nueva caja local
            $resultado = $corteCaja->iniciarCaja(
                $monto_inicial,
                1, // Usuario del sistema
                "Apertura automática sincronizada desde Nube (Corte Nube ID: {$id_corte_nube})"
            );
            
            if ($resultado['success']) {
                log_sync("✅ Caja local abierta exitosamente", 'SUCCESS');
                log_sync("   Nuevo Corte local ID: {$resultado['id_corte']}", 'INFO');
                log_sync("   Monto inicial: $" . number_format($monto_inicial, 2), 'INFO');
                
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Caja local abierta y sincronizada',
                    'id_corte_local' => $resultado['id_corte'],
                    'id_corte_nube' => $id_corte_nube,
                    'monto_inicial' => $monto_inicial
                ]);
            } else {
                log_sync("❌ Error al abrir caja local: {$resultado['mensaje']}", 'ERROR');
                echo json_encode([
                    'success' => false,
                    'error' => $resultado['mensaje']
                ]);
            }
            break;
            
        case 'verificar_estado':
            // Endpoint para verificar el estado de sincronización
            $estado = [
                'caja_activa' => $corteCaja->hayCajaActiva(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            if ($corteCaja->hayCajaActiva()) {
                $corte = $corteCaja->getCorteActual();
                $estado['id_corte'] = $corte['id'];
                $estado['fecha_apertura'] = $corte['fecha'] . ' ' . $corte['hora'];
                $estado['hora_cierre_programada'] = $corte['hora_cierre_programada'] ?? null;
            }
            
            log_sync("📊 Estado verificado", 'INFO');
            echo json_encode([
                'success' => true,
                'estado' => $estado
            ]);
            break;
            
        default:
            throw new Exception("Acción no reconocida: {$accion}");
    }
    
    log_sync("=== Sincronización completada ===\n", 'END');
    
} catch (Exception $e) {
    log_sync("❌ ERROR: {$e->getMessage()}", 'ERROR');
    log_sync("   Archivo: {$e->getFile()}", 'ERROR');
    log_sync("   Línea: {$e->getLine()}", 'ERROR');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Cerrar conexión
if (isset($conn) && $conn) {
    $conn->close();
}
