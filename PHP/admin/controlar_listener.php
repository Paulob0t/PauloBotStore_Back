<?php
/**
 * API para controlar el listener del monedero
 * Permite iniciar, detener y verificar el estado del listener
 */

header('Content-Type: application/json');

// Verificar sesión (opcional, descomenta si quieres proteger)
// session_start();
// if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
//     echo json_encode(['success' => false, 'message' => 'No autorizado']);
//     exit();
// }

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// Rutas a los scripts (ajusta según tu estructura)
$directorioBase = dirname(dirname(__DIR__)); // Sube 2 niveles desde admin/dist
$iniciarScript = $directorioBase . DIRECTORY_SEPARATOR . 'iniciar_listener_invisible.vbs';
$detenerScript = $directorioBase . DIRECTORY_SEPARATOR . 'detener_listener.bat';

switch ($accion) {
    case 'iniciar':
        iniciarListener($iniciarScript);
        break;
    
    case 'detener':
        detenerListener($detenerScript);
        break;
    
    case 'estado':
        verificarEstado();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function iniciarListener($script) {
    // Primero verificar si ya está corriendo
    $estado = verificarEstadoInterno();
    
    if ($estado['corriendo']) {
        echo json_encode([
            'success' => false,
            'message' => 'El listener ya está en ejecución',
            'estado' => 'corriendo'
        ]);
        return;
    }
    
    // Obtener rutas
    $directorioBase = dirname(dirname(__DIR__));
    $phpExe = 'C:\\xampp\\php\\php.exe';
    $listenerScript = $directorioBase . '\\com5_manager.php';
    $batScript = __DIR__ . '\\_iniciar_listener_web.bat';
    
    // Verificar que exista PHP
    if (!file_exists($phpExe)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encuentra PHP en: ' . $phpExe
        ]);
        return;
    }
    
    // Verificar que exista el script del listener
    if (!file_exists($listenerScript)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se encuentra com5_manager.php en: ' . $listenerScript
        ]);
        return;
    }
    
    $metodoUsado = '';
    $exito = false;
    
    // MÉTODO 1: Intentar con COM (WScript.Shell)
    if (class_exists('COM')) {
        try {
            $WshShell = new COM("WScript.Shell");
            $comando = '"' . $phpExe . '" "' . $listenerScript . '"';
            $WshShell->Run($comando, 0, false);
            $metodoUsado = 'COM WScript.Shell';
            $exito = true;
        } catch (Exception $e) {
            $metodoUsado = 'COM falló: ' . $e->getMessage();
        }
    }
    
    // MÉTODO 2: Si COM falla, usar BAT file
    if (!$exito && file_exists($batScript)) {
        try {
            pclose(popen('start /B "" "' . $batScript . '"', 'r'));
            $metodoUsado = 'BAT Script';
            $exito = true;
        } catch (Exception $e) {
            $metodoUsado .= ' | BAT falló: ' . $e->getMessage();
        }
    }
    
    // MÉTODO 3: Usar PowerShell como último recurso
    if (!$exito) {
        $psCommand = 'powershell.exe -WindowStyle Hidden -Command "Start-Process -WindowStyle Hidden -FilePath \'' . $phpExe . '\' -ArgumentList \'' . $listenerScript . '\' -WorkingDirectory \'' . $directorioBase . '\'"';
        exec($psCommand . ' 2>&1', $output, $returnVar);
        $metodoUsado = 'PowerShell (fallback)';
        $exito = ($returnVar === 0);
    }
    
    // Esperar un momento para que inicie
    sleep(3);
    
    // Verificar si se inició correctamente
    $nuevoEstado = verificarEstadoInterno();
    
    echo json_encode([
        'success' => $nuevoEstado['corriendo'],
        'message' => $nuevoEstado['corriendo'] ? 
            'Listener iniciado correctamente' : 
            'Listener ejecutado pero no se detecta aún. Espera 5 segundos y actualiza.',
        'estado' => $nuevoEstado['corriendo'] ? 'corriendo' : 'iniciando',
        'metodo' => $metodoUsado,
        'procesos' => $nuevoEstado['cantidad']
    ]);
}

function detenerListener($script) {
    // Método directo: matar procesos PHP del listener
    $comando = 'wmic process where "name=\'php.exe\' and commandline like \'%com5_manager%\'" delete 2>&1';
    exec($comando, $output, $returnVar);
    
    // Esperar un momento
    sleep(1);
    
    $estado = verificarEstadoInterno();
    
    echo json_encode([
        'success' => !$estado['corriendo'],
        'message' => !$estado['corriendo'] ? 'Listener detenido correctamente' : 'Error al detener listener',
        'estado' => $estado['corriendo'] ? 'corriendo' : 'detenido'
    ]);
}

function verificarEstado() {
    $estado = verificarEstadoInterno();
    echo json_encode([
        'success' => true,
        'corriendo' => $estado['corriendo'],
        'estado' => $estado['corriendo'] ? 'corriendo' : 'detenido',
        'procesos' => $estado['cantidad']
    ]);
}

function verificarEstadoInterno() {
    // Método 1: Buscar procesos PHP que ejecuten com5_manager
    $comando = 'wmic process where "name=\'php.exe\'" get commandline 2>&1';
    exec($comando, $output);
    
    $corriendo = false;
    $cantidad = 0;
    
    foreach ($output as $linea) {
        if (stripos($linea, 'com5_manager') !== false) {
            $corriendo = true;
            $cantidad++;
        }
    }
    
    // Método 2: Si el método 1 falla, usar tasklist
    if (!$corriendo) {
        $comando2 = 'tasklist /FI "IMAGENAME eq php.exe" /V 2>&1';
        exec($comando2, $output2);
        
        foreach ($output2 as $linea) {
            if (stripos($linea, 'com5_manager') !== false) {
                $corriendo = true;
                $cantidad++;
            }
        }
    }
    
    return [
        'corriendo' => $corriendo,
        'cantidad' => $cantidad
    ];
}
