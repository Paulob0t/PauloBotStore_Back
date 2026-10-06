<?php
/**
 * Archivo para enviar comandos al Monedero por puerto serial
 * Compatible con Windows utilizando PowerShell para comunicación serial
 * Mantiene sesión del puerto abierto para múltiples comandos
 * 
 * Fecha: 21-01-2026
 */

session_start();
header('Content-Type: application/json');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

// Obtener acción
$accion = isset($_POST['accion']) ? trim($_POST['accion']) : 'enviar';

// Manejar diferentes acciones
switch ($accion) {
    case 'abrir':
        abrirPuerto();
        break;
    case 'cerrar':
        cerrarPuerto();
        break;
    case 'enviar':
        enviarComando();
        break;
    default:
        echo json_encode([
            'success' => false,
            'mensaje' => 'Acción no válida'
        ]);
}

exit;

/**
 * Abre el puerto serial y guarda la configuración en sesión
 */
function abrirPuerto() {
    $puerto = isset($_POST['puerto']) ? trim($_POST['puerto']) : 'COM5';
    $baudrate = isset($_POST['baudrate']) ? intval($_POST['baudrate']) : 9600;
    $databits = isset($_POST['databits']) ? intval($_POST['databits']) : 8;
    $parity = isset($_POST['parity']) ? trim($_POST['parity']) : 'none';
    $terminador = isset($_POST['terminador']) ? trim($_POST['terminador']) : 'crlf';
    $formatoHex = isset($_POST['formato_hex']) && $_POST['formato_hex'] == '1';

    // Validar que el puerto esté disponible
    $resultado = verificarPuerto($puerto, $baudrate, $databits, $parity);
    
    if ($resultado['success']) {
        // Guardar configuración en sesión
        $_SESSION['puerto_serial'] = [
            'puerto' => $puerto,
            'baudrate' => $baudrate,
            'databits' => $databits,
            'parity' => $parity,
            'terminador' => $terminador,
            'formato_hex' => $formatoHex,
            'abierto' => true,
            'timestamp' => time()
        ];

        registrarLog("PUERTO ABIERTO: $puerto @ $baudrate baud, Terminador: $terminador, HEX: " . ($formatoHex ? 'SI' : 'NO'));

        echo json_encode([
            'success' => true,
            'mensaje' => "Puerto $puerto abierto exitosamente",
            'config' => $_SESSION['puerto_serial']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => $resultado['error']
        ]);
    }
}

/**
 * Cierra el puerto serial
 */
function cerrarPuerto() {
    if (isset($_SESSION['puerto_serial'])) {
        $puerto = $_SESSION['puerto_serial']['puerto'];
        registrarLog("PUERTO CERRADO: $puerto");
        unset($_SESSION['puerto_serial']);
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Puerto cerrado exitosamente'
    ]);
}

/**
 * Envía un comando al puerto abierto
 */
function enviarComando() {
    // Verificar que el puerto esté abierto
    if (!isset($_SESSION['puerto_serial']) || !$_SESSION['puerto_serial']['abierto']) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'El puerto no está abierto. Abre el puerto primero.'
        ]);
        return;
    }

    $comando = isset($_POST['comando']) ? trim($_POST['comando']) : '';
    
    // Validar comando
    if (empty($comando)) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Comando vacío'
        ]);
        return;
    }

    // Obtener configuración del puerto de la sesión
    $config = $_SESSION['puerto_serial'];
    $puerto = $config['puerto'];
    $baudrate = $config['baudrate'];
    $databits = $config['databits'];
    $parity = $config['parity'];
    $terminador = $config['terminador'];
    $formatoHex = $config['formato_hex'];

    // Procesar el comando según el formato
    $comandoProcesado = procesarComando($comando, $formatoHex, $terminador);
    
    registrarLog("CMD: $comando -> Procesado: " . bin2hex($comandoProcesado) . " -> $puerto");

    try {
        // Enviar comando usando PowerShell
        $resultado = enviarComandoSerial($comandoProcesado, $puerto, $baudrate, $databits, $parity);
        
        if ($resultado['success']) {
            $respuestaDisplay = !empty($resultado['respuesta']) ? $resultado['respuesta'] : 'Sin respuesta';
            echo json_encode([
                'success' => true,
                'mensaje' => 'Comando enviado exitosamente',
                'comando' => $comando,
                'respuesta' => $respuestaDisplay,
                'puerto' => $puerto,
                'debug' => 'Enviado (HEX): ' . bin2hex($comandoProcesado)
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'mensaje' => $resultado['error'],
                'comando' => $comando
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Verifica que el puerto esté disponible
 */
function verificarPuerto($puerto, $baudrate, $databits, $parity) {
    // Convertir parity a formato PowerShell
    $parityMap = [
        'none' => 'None',
        'even' => 'Even',
        'odd' => 'Odd'
    ];
    $parityPS = $parityMap[$parity] ?? 'None';
    
    // Script de PowerShell para verificar puerto
    $psScript = <<<POWERSHELL
\$ErrorActionPreference = "Stop"
try {
    \$port = New-Object System.IO.Ports.SerialPort
    \$port.PortName = "$puerto"
    \$port.BaudRate = $baudrate
    \$port.DataBits = $databits
    \$port.Parity = [System.IO.Ports.Parity]::$parityPS
    \$port.StopBits = [System.IO.Ports.StopBits]::One
    \$port.ReadTimeout = 1000
    \$port.WriteTimeout = 1000
    
    \$port.Open()
    \$port.Close()
    
    Write-Output "SUCCESS:Puerto disponible"
} catch {
    Write-Output "ERROR:\$_"
}
POWERSHELL;

    $tempScript = sys_get_temp_dir() . '/check_serial_' . uniqid() . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    $psCommand = sprintf(
        'powershell.exe -ExecutionPolicy Bypass -File "%s" 2>&1',
        $tempScript
    );
    
    $output = [];
    exec($psCommand, $output);
    @unlink($tempScript);
    
    $outputStr = implode("\n", $output);
    
    if (strpos($outputStr, 'SUCCESS:') === 0) {
        return ['success' => true];
    } else {
        $error = strpos($outputStr, 'ERROR:') === 0 ? substr($outputStr, 6) : $outputStr;
        return [
            'success' => false,
            'error' => 'No se pudo abrir el puerto: ' . trim($error)
        ];
    }
}

/**
 * Registra en el archivo de log
 */
function registrarLog($mensaje) {
    $logFile = __DIR__ . '/logs/monedero_commands.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    
    $logEntry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $mensaje);
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Procesa el comando según el formato y terminador
 */
function procesarComando($comando, $formatoHex, $terminador) {
    $comandoProcesado = '';
    
    // Si es formato HEX, convertir de hex string a bytes
    if ($formatoHex) {
        // Remover espacios y convertir pares hex a bytes
        $hex = str_replace(' ', '', $comando);
        $comandoProcesado = hex2bin($hex);
    } else {
        // Texto ASCII normal
        $comandoProcesado = $comando;
    }
    
    // Agregar terminador
    switch ($terminador) {
        case 'cr':
            $comandoProcesado .= "\r";
            break;
        case 'lf':
            $comandoProcesado .= "\n";
            break;
        case 'crlf':
            $comandoProcesado .= "\r\n";
            break;
        case 'none':
        default:
            // Sin terminador
            break;
    }
    
    return $comandoProcesado;
}

/**
 * Envía un comando al puerto serial usando PowerShell (Windows)
 */
function enviarComandoSerial($comando, $puerto, $baudrate, $databits, $parity) {
    // Convertir parity a formato PowerShell
    $parityMap = [
        'none' => 'None',
        'even' => 'Even',
        'odd' => 'Odd',
        'mark' => 'Mark',
        'space' => 'Space'
    ];
    $parityPS = $parityMap[$parity] ?? 'None';
    
    // Convertir comando a base64 para transmitirlo sin problemas de escape
    $comandoBase64 = base64_encode($comando);
    
    // Script de PowerShell para comunicación serial
    $psScript = <<<POWERSHELL
\$ErrorActionPreference = "Stop"
try {
    \$port = New-Object System.IO.Ports.SerialPort
    \$port.PortName = "$puerto"
    \$port.BaudRate = $baudrate
    \$port.DataBits = $databits
    \$port.Parity = [System.IO.Ports.Parity]::$parityPS
    \$port.StopBits = [System.IO.Ports.StopBits]::One
    \$port.ReadTimeout = 3000
    \$port.WriteTimeout = 3000
    
    \$port.Open()
    
    # Decodificar comando de base64 a bytes
    \$comandoBytes = [System.Convert]::FromBase64String("$comandoBase64")
    
    # Enviar bytes raw
    \$port.Write(\$comandoBytes, 0, \$comandoBytes.Length)
    
    # Dar tiempo al dispositivo para procesar
    Start-Sleep -Milliseconds 800
    
    # Intentar leer respuesta múltiples veces
    \$response = ""
    \$intentos = 0
    while (\$intentos -lt 5) {
        if (\$port.BytesToRead -gt 0) {
            \$response += \$port.ReadExisting()
            Start-Sleep -Milliseconds 100
        } else {
            if (\$response.Length -gt 0) {
                break
            }
            Start-Sleep -Milliseconds 200
        }
        \$intentos++
    }
    
    \$port.Close()
    
    # Convertir respuesta a HEX para debugging
    \$responseHex = ""
    if (\$response.Length -gt 0) {
        \$bytes = [System.Text.Encoding]::ASCII.GetBytes(\$response)
        \$responseHex = [System.BitConverter]::ToString(\$bytes) -replace '-',''
    }
    
    Write-Output "SUCCESS:\$response|HEX:\$responseHex"
} catch {
    Write-Output "ERROR:\$_"
}
POWERSHELL;

    // Guardar script temporal
    $tempScript = sys_get_temp_dir() . '/serial_cmd_' . uniqid() . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    // Ejecutar PowerShell
    $psCommand = sprintf(
        'powershell.exe -ExecutionPolicy Bypass -File "%s" 2>&1',
        $tempScript
    );
    
    $output = [];
    $returnVar = 0;
    exec($psCommand, $output, $returnVar);
    
    // Eliminar script temporal
    @unlink($tempScript);
    
    $outputStr = implode("\n", $output);
    
    if (strpos($outputStr, 'SUCCESS:') === 0) {
        // Parsear respuesta y HEX
        $data = substr($outputStr, 8); // Remover "SUCCESS:"
        $parts = explode('|HEX:', $data);
        $respuesta = $parts[0];
        $responseHex = isset($parts[1]) ? $parts[1] : '';
        
        $mensajeRespuesta = trim($respuesta);
        if (empty($mensajeRespuesta)) {
            $mensajeRespuesta = 'Sin respuesta del dispositivo';
        }
        if (!empty($responseHex)) {
            $mensajeRespuesta .= ' [HEX: ' . $responseHex . ']';
        }
        
        return [
            'success' => true,
            'respuesta' => $mensajeRespuesta
        ];
    } else if (strpos($outputStr, 'ERROR:') === 0) {
        $error = substr($outputStr, 6); // Remover "ERROR:"
        return [
            'success' => false,
            'error' => trim($error)
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Respuesta desconocida: ' . $outputStr
        ];
    }
}

/**
 * Función alternativa usando comando MODE de Windows (más simple pero menos confiable)
 */
function enviarComandoSerialMODE($comando, $puerto, $baudrate) {
    // Configurar puerto
    $modeCmd = sprintf('mode %s: BAUD=%d PARITY=N DATA=8 STOP=1', $puerto, $baudrate);
    exec($modeCmd, $output1, $return1);
    
    if ($return1 !== 0) {
        return [
            'success' => false,
            'error' => 'No se pudo configurar el puerto serial. Verificar que exista.'
        ];
    }
    
    // Intentar escribir al puerto (NOTA: esto es limitado en Windows)
    // Esta función es más para testing, la de PowerShell es más robusta
    $tempFile = sys_get_temp_dir() . '/serial_data.txt';
    file_put_contents($tempFile, $comando . "\r\n");
    
    $copyCmd = sprintf('type "%s" > %s:', $tempFile, $puerto);
    exec($copyCmd, $output2, $return2);
    
    @unlink($tempFile);
    
    if ($return2 === 0) {
        return [
            'success' => true,
            'respuesta' => 'Comando enviado (modo simple)'
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Error al enviar comando al puerto'
        ];
    }
}
