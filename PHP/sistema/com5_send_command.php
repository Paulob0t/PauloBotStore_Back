<?php
/**
 * 🔌 ENVIAR COMANDOS AL COM5 MANAGER (Proceso Hijo)
 * ===================================================
 * Este archivo NO abre COM5 directamente.
 * Solo envía comandos al proceso PADRE que tiene COM5 abierto.
 * 
 * USO:
 * - Dispensar cambio: com5_send_command.php?action=dispense&amount=10
 * - Habilitar aceptador: com5_send_command.php?action=enable
 * - Deshabilitar: com5_send_command.php?action=disable
 * - Reset: com5_send_command.php?action=reset
 * 
 * ARQUITECTURA:
 * [monedero_api.php] ---> [com5_send_command.php] ---> [cola] ---> [com5_manager.php] ---> [COM5]
 *      (hijo)                  (hijo)                             (PADRE con puerto)
 * 
 * Fecha: 30-04-2026
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('CMD_QUEUE_FILE', __DIR__ . '/admin/dist/logs/com5_commands.queue');
define('MANAGER_PID_FILE', __DIR__ . '/admin/dist/logs/com5_manager.pid');
define('LOG_FILE', __DIR__ . '/admin/dist/logs/com5_send_command.log');

/**
 * Logger
 */
function logCommand($mensaje) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $mensaje\n";
    file_put_contents(LOG_FILE, $log, FILE_APPEND);
}

/**
 * Verificar si el manager está corriendo
 */
function isManagerRunning() {
    if (!file_exists(MANAGER_PID_FILE)) {
        return false;
    }
    
    $pid = (int)file_get_contents(MANAGER_PID_FILE);
    
    exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
    foreach ($output as $line) {
        if (strpos($line, "php.exe") !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Enviar comando a la cola del manager
 */
function sendCommandToManager($command) {
    $comandos = [];
    
    // Leer comandos existentes
    if (file_exists(CMD_QUEUE_FILE)) {
        $json = file_get_contents(CMD_QUEUE_FILE);
        $comandos = json_decode($json, true) ?: [];
    }
    
    // Agregar nuevo comando
    $comandos[] = [
        'command' => $command,
        'timestamp' => date('Y-m-d H:i:s'),
        'source' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
    ];
    
    // Guardar
    file_put_contents(CMD_QUEUE_FILE, json_encode($comandos, JSON_PRETTY_PRINT));
    
    logCommand("📤 Comando enviado a cola: $command");
    
    return true;
}

/**
 * Convertir monto a comando hexadecimal
 */
function createDispenseCommand($monto) {
    // INT000[HEX]003
    // Ejemplos:
    // $10 = 0xA = INT000A003
    // $20 = 0x14 = INT00014003
    // $5 = 0x5 = INT0005003
    
    $hex = strtoupper(dechex($monto));
    $hex = str_pad($hex, 3, '0', STR_PAD_LEFT); // Mínimo 3 dígitos
    
    return "INT000{$hex}003";
}

// ===========================
// MAIN - Procesar solicitud
// ===========================

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if (empty($action)) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'No se especificó acción',
            'acciones_disponibles' => ['dispense', 'enable', 'disable', 'reset', 'status']
        ]);
        exit;
    }
    
    // Verificar que el manager esté corriendo (excepto para status)
    if ($action !== 'status' && !isManagerRunning()) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'COM5 Manager no está corriendo',
            'solucion' => 'Ejecuta: php com5_manager.php'
        ]);
        exit;
    }
    
    switch ($action) {
        case 'status':
            $running = isManagerRunning();
            echo json_encode([
                'error' => false,
                'manager_running' => $running,
                'mensaje' => $running ? 'Manager activo' : 'Manager detenido',
                'pid_file' => file_exists(MANAGER_PID_FILE) ? file_get_contents(MANAGER_PID_FILE) : null
            ]);
            break;
        
        case 'dispense':
            $monto = (float)($_GET['amount'] ?? $_POST['amount'] ?? 0);
            
            if ($monto <= 0) {
                echo json_encode([
                    'error' => true,
                    'mensaje' => 'Monto inválido'
                ]);
                exit;
            }
            
            $command = createDispenseCommand($monto);
            sendCommandToManager($command);
            
            logCommand("💰 Dispensar: $$monto → $command");
            
            echo json_encode([
                'error' => false,
                'mensaje' => "Comando de dispensar $$monto enviado",
                'comando' => $command,
                'esperando_procesamiento' => true
            ]);
            break;
        
        case 'enable':
            sendCommandToManager("INT0000001");
            echo json_encode([
                'error' => false,
                'mensaje' => 'Aceptador habilitado',
                'comando' => 'INT0000001'
            ]);
            break;
        
        case 'disable':
            sendCommandToManager("INT0000002");
            echo json_encode([
                'error' => false,
                'mensaje' => 'Aceptador deshabilitado',
                'comando' => 'INT0000002'
            ]);
            break;
        
        case 'reset':
            sendCommandToManager("INT0000000");
            echo json_encode([
                'error' => false,
                'mensaje' => 'Hardware reseteado',
                'comando' => 'INT0000000'
            ]);
            break;
        
        default:
            echo json_encode([
                'error' => true,
                'mensaje' => 'Acción no válida',
                'acciones_disponibles' => ['dispense', 'enable', 'disable', 'reset', 'status']
            ]);
    }
    
} catch (Exception $e) {
    logCommand("❌ Error: " . $e->getMessage());
    
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error: ' . $e->getMessage()
    ]);
}
